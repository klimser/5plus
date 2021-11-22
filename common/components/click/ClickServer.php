<?php

namespace common\components\click;

use common\components\ComponentContainer;
use common\components\MoneyComponent;
use common\models\Contract;
use common\models\GiftCard;
use common\service\payment\AbstractPaymentServer;
use DateTime;
use Throwable;
use Yii;
use yii\web\Request;
use yii\web\Response;

class ClickServer extends AbstractPaymentServer
{
    public function processPrepare(Request $request): Response
    {
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $jsonData = ['error' => -8];
        if (!$request->isPost) {
            $jsonData['error_note'] = 'Request should be POST';
            $response->data = $jsonData;
            return $response;
        }

        $params = $request->post();
        if (empty($params)
            || !array_key_exists('click_trans_id', $params)
            || !array_key_exists('service_id', $params)
            || !array_key_exists('merchant_trans_id', $params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('action', $params)
            || !array_key_exists('sign_time', $params)
            || !array_key_exists('sign_string', $params)) {
            $jsonData['error_note'] = 'Invalid request';
            $response->data = $jsonData;
            return $response;
        }

        $hash = $params['click_trans_id'] . $params['service_id'] . ComponentContainer::getClickApi()->secretKey
            . $params['merchant_trans_id'] . $params['amount'] . $params['action'] . $params['sign_time'];
        $sign = md5($hash);

        if ($sign !== $params['sign_string']) {
            $jsonData['error'] = -1;
            $jsonData['error_note'] = 'Invalid signature';
            $response->data = $jsonData;
            return $response;
        }

        switch ($this->getTypeById($params['merchant_trans_id'])) {
            case Contract::class:
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $contract = $this->getContractById($params['merchant_trans_id'], (int)$params['amount'] * 100);

                    $contract->status = Contract::STATUS_PROCESS;
                    $contract->external_id = $params['click_trans_id'];
                    $contract->save();
                    $transaction->commit();
                } catch (Throwable $exception) {
                    $transaction->rollBack();
                    $jsonData['error'] = -7;
                    $jsonData['error_note'] = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                    $response->data = $jsonData;
                    return $response;
                }
                $response->data = [
                    'click_trans_id' => $params['click_trans_id'],
                    'merchant_trans_id' => $params['merchant_trans_id'],
                    'merchant_prepare_id' => $contract->id,
                    'error' => 0,
                ];
                return $response;
            case GiftCard::class:
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $giftCard = $this->getGiftCardById($params['merchant_trans_id'], (int)$params['amount'] * 100);
                } catch (Throwable $exception) {
                        $transaction->rollBack();
                        $jsonData['error'] = -7;
                        $jsonData['error_note'] = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                        $response->data = $jsonData;
                        return $response;
                    }

                $response->data = [
                    'click_trans_id' => $params['click_trans_id'],
                    'merchant_trans_id' => $params['merchant_trans_id'],
                    'merchant_prepare_id' => $giftCard->id,
                    'error' => 0,
                ];
                return $response;
        }

        $jsonData['error_note'] = 'Unknown error';
        $response->data = $jsonData;
        return $response;
    }

    public function handle(Request $request): Response
    {
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $jsonData = ['error' => -8];
        if (!$request->isPost) {
            $jsonData['error_note'] = 'Request should be POST';
            $response->data = $jsonData;
            return $response;
        }

        $params = $request->post();
        if (empty($params)
            || !array_key_exists('click_trans_id', $params)
            || !array_key_exists('service_id', $params)
            || !array_key_exists('merchant_trans_id', $params)
            || !array_key_exists('merchant_prepare_id', $params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('action', $params)
            || !array_key_exists('sign_time', $params)
            || !array_key_exists('sign_string', $params)) {
            $jsonData['error_note'] = 'Invalid request';
            $response->data = $jsonData;
            return $response;
        }
        
        if (array_key_exists('error', $params) && $params['error'] != 0) {
            $jsonData['error'] = -9;
            $jsonData['error_note'] = 'Payment cancelled';
            $response->data = $jsonData;
            return $response;
        }

        $hash = $params['click_trans_id'] . $params['service_id'] . ComponentContainer::getClickApi()->secretKey
            . $params['merchant_trans_id'] . $params['merchant_prepare_id'] . $params['amount'] . $params['action'] . $params['sign_time'];
        $sign = md5($hash);

        if ($sign !== $params['sign_string']) {
            $jsonData['error'] = -1;
            $jsonData['error_note'] = 'Invalid signature';
            $response->data = $jsonData;
            return $response;
        }

        switch ($this->getTypeById($params['merchant_trans_id'])) {
            case Contract::class:
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $contract = $this->getContractById($params['merchant_trans_id'], (int) $params['amount'] * 100);
                    $paymentId = MoneyComponent::payContract(
                        $contract,
                        new DateTime($params['sign_time']),
                        $this->getPaymentTypeId(),
                        $params['click_paydoc_id']
                    );

                    $contract->external_id = $params['click_trans_id'];
                    $contract->save();

                    $transaction->commit();
                } catch (Throwable $exception) {
                    $transaction->rollBack();
                    $jsonData['error'] = -7;
                    $jsonData['error_note'] = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                    ComponentContainer::getErrorLogger()->logError('api/click', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                    $response->data = $jsonData;
                    return $response;
                }

                $response->data = [
                    'click_trans_id' => $params['click_trans_id'],
                    'merchant_trans_id' => $params['merchant_trans_id'],
                    'merchant_confirm_id' => $paymentId,
                    'error' => 0,
                ];
                return $response;
            case GiftCard::class:
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $giftCard = $this->getGiftCardById($params['merchant_trans_id'], (int) $params['amount'] * 100);
                    
                    $giftCard->status = GiftCard::STATUS_PAID;
                    $giftCard->paid_at = $params['sign_time'];
                    if (!$giftCard->save()) {
                        ComponentContainer::getErrorLogger()->logError('api/click', $giftCard->getErrorsAsString(), true);
                        $jsonData['error'] = -7;
                        $jsonData['error_note'] = 'Ошибка регистрации оплаты';
                        $response->data = $jsonData;
                        return $response;
                    }

                    ComponentContainer::getMailQueue()->add(
                        'Квитанция об оплате',
                        $giftCard->customer_email,
                        'gift-card-html',
                        'gift-card-text',
                        ['id' => $giftCard->id]
                    );

                    $transaction->commit();
                } catch (Throwable $exception) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()->logError('api/paymo', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                    $jsonData['error_note'] = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                    $response->data = $jsonData;
                    return $response;
                }

                $response->data = [
                    'click_trans_id' => $params['click_trans_id'],
                    'merchant_trans_id' => $params['merchant_trans_id'],
                    'merchant_confirm_id' => null,
                    'error' => 0,
                ];
                return $response;
        }

        $jsonData['error_note'] = 'Unknown error';
        $response->data = $jsonData;
        return $response;
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_CLICK;
    }
}
