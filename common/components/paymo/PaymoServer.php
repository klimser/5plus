<?php

namespace common\components\paymo;

use common\components\ComponentContainer;
use common\components\MoneyComponent;
use common\models\Contract;
use common\models\GiftCard;
use common\service\payment\AbstractPaymentServer;
use common\service\payment\PaymentServiceException;
use Yii;
use yii\web\Request;
use yii\web\Response;

class PaymoServer extends AbstractPaymentServer
{
    private const IP_WHITELIST = ['185.8.212.47', '185.8.212.48'];

    public function handle(Request $request): Response
    {
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $jsonData = ['status' => 0];

        if (!$request->isPost) {
            $jsonData['message'] = 'Request should be POST';
            $response->data = $jsonData;
            return $response;
        }
        if (!in_array($request->remoteIP, self::IP_WHITELIST)) {
            $jsonData['message'] = 'Wrong server IP';
            $response->data = $jsonData;
            return $response;
        }

        $params = json_decode($request->rawBody, true);
        if (false === $params
            || !array_key_exists('store_id', $params)
            || !array_key_exists('transaction_id', $params)
            || !array_key_exists('invoice', $params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('sign', $params)) {
            $jsonData['message'] = 'Invalid JSON-body';
            $response->data = $jsonData;
            return $response;
        }

        $hash = $params['store_id'] . $params['transaction_id'] . $params['invoice'] . $params['amount'] . ComponentContainer::getPaymoApi()->apiKey;
        $sign = md5($hash);

        if ($sign !== $params['sign']) {
            $jsonData['message'] = 'Invalid signature';
            $response->data = $jsonData;
            return $response;
        }

        try {
            switch ($this->getTypeById($params['invoice'])) {
                case Contract::class:
                    $contract = $this->getContractById($params['invoice'], $params['amount']);

                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        MoneyComponent::payContract(
                            $contract,
                            new \DateTime(array_key_exists('transaction_time', $params) ? $params['transaction_time'] : 'now'),
                            Contract::PAYMENT_TYPE_ATMOS,
                            $params['transaction_id']
                        );
                        $transaction->commit();
                    } catch (\Throwable $exception) {
                        $transaction->rollBack();
                        $jsonData['message'] = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                        ComponentContainer::getErrorLogger()->logError('api/paymo', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                        $response->data = $jsonData;
                        return $response;
                    }
                    break;
                case GiftCard::class:
                    $giftCard = $this->getGiftCardById($params['invoice'], $params['amount']);

                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        $giftCard->status = GiftCard::STATUS_PAID;
                        $giftCard->paid_at = array_key_exists('transaction_time', $params) ? $params['transaction_time'] : date('Y-m-d H:i:s');
                        if (!$giftCard->save()) {
                            $transaction->rollBack();
                            ComponentContainer::getErrorLogger()->logError('api/paymo', $giftCard->getErrorsAsString(), true);
                            $jsonData['message'] = 'Внутренняя ошибка сервера';
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
                    } catch (\Throwable $exception) {
                        $transaction->rollBack();
                        ComponentContainer::getErrorLogger()->logError('api/paymo', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                        $jsonData['message'] = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                        $response->data = $jsonData;
                        return $response;
                    }
                    break;
            }
        } catch (PaymentServiceException $ex) {
            $jsonData['message'] = $ex->getMessage();
            $response->data = $jsonData;
            return $response;
        }

        $jsonData['status'] = 1;
        $jsonData['message'] = 'Успешно';
        $response->data = $jsonData;
        return $response;
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_ATMOS;
    }
}
