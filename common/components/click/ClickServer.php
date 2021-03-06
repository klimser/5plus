<?php

namespace common\components\click;

use common\components\ComponentContainer;
use common\components\MoneyComponent;
use common\models\Contract;
use common\models\GiftCard;
use Yii;

class ClickServer
{
    public function processPrepare(): array
    {
        $jsonData = ['error' => -8];
        if (!Yii::$app->request->isPost) {
            $jsonData['error_note'] = 'Request should be POST';
            return $jsonData;
        }

        $params = Yii::$app->request->post();
        if (empty($params)
            || !array_key_exists('click_trans_id', $params)
            || !array_key_exists('service_id', $params)
            || !array_key_exists('merchant_trans_id', $params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('action', $params)
            || !array_key_exists('sign_time', $params)
            || !array_key_exists('sign_string', $params)) {
            $jsonData['error_note'] = 'Invalid request';
            return $jsonData;
        }

        $hash = $params['click_trans_id'] . $params['service_id'] . ComponentContainer::getClickApi()->secretKey
            . $params['merchant_trans_id'] . $params['amount'] . $params['action'] . $params['sign_time'];
        $sign = md5($hash);

        if ($sign !== $params['sign_string']) {
            $jsonData['error'] = -1;
            $jsonData['error_note'] = 'Invalid signature';
            return $jsonData;
        }

        if (preg_match('#^gc-(\d+)$#', $params['merchant_trans_id'], $matches)) {
            /** @var GiftCard $giftCard */
            $giftCard = GiftCard::findOne(intval($matches[1]));

            if (!$giftCard) {
                $jsonData['error'] = -5;
                $jsonData['error_note'] = 'Invoice not found';
                return $jsonData;
            }
            
            if ($giftCard->status != GiftCard::STATUS_NEW) {
                $jsonData['error'] = -4;
                $jsonData['error_note'] = 'Инвойс уже оплачен, повторная оплата невозможна';
                return $jsonData;
            }
            if (bccomp($giftCard->amount, $params['amount'], 2) !== 0) {
                $jsonData['error'] = -2;
                $jsonData['error_note'] = 'Wrong amount';
                return $jsonData;
            }
            
            return [
                'click_trans_id' => $params['click_trans_id'],
                'merchant_trans_id' => $params['merchant_trans_id'],
                'merchant_prepare_id' => $giftCard->id,
                'error' => 0,
            ];
        } else {
            /** @var Contract $contract */
            $contract = Contract::findOne(['number' => $params['merchant_trans_id']]);

            if (!$contract) {
                $jsonData['error'] = -5;
                $jsonData['error_note'] = 'Invoice not found';
                return $jsonData;
            }
            
            if ($contract->status == Contract::STATUS_PAID) {
                $jsonData['error'] = -4;
                $jsonData['error_note'] = 'Инвойс уже оплачен, повторная оплата невозможна';
                return $jsonData;
            }
            
            if (bccomp($contract->amount, $params['amount'], 2) !== 0) {
                $jsonData['error'] = -2;
                $jsonData['error_note'] = 'Wrong amount';
                return $jsonData;
            }
            
            $contract->status = Contract::STATUS_PROCESS;
            $contract->external_id = $params['click_trans_id'];
            $contract->save();

            return [
                'click_trans_id' => $params['click_trans_id'],
                'merchant_trans_id' => $params['merchant_trans_id'],
                'merchant_prepare_id' => $contract->id,
                'error' => 0,
            ];
        }
    }

    public function processComplete(): array
    {
        $jsonData = ['error' => -8];
        if (!Yii::$app->request->isPost) {
            $jsonData['error_note'] = 'Request should be POST';
            return $jsonData;
        }

        $params = Yii::$app->request->post();
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
            return $jsonData;
        }
        
        if (array_key_exists('error', $params) && $params['error'] != 0) {
            $jsonData['error'] = -9;
            $jsonData['error_note'] = 'Payment cancelled';
            return $jsonData;
        }

        $hash = $params['click_trans_id'] . $params['service_id'] . ComponentContainer::getClickApi()->secretKey
            . $params['merchant_trans_id'] . $params['merchant_prepare_id'] . $params['amount'] . $params['action'] . $params['sign_time'];
        $sign = md5($hash);

        if ($sign !== $params['sign_string']) {
            $jsonData['error'] = -1;
            $jsonData['error_note'] = 'Invalid signature';
            return $jsonData;
        }

        if (preg_match('#^gc-(\d+)$#', $params['merchant_trans_id'], $matches)) {
            /** @var GiftCard $giftCard */
            $giftCard = GiftCard::findOne(intval($matches[1]));

            if (!$giftCard) {
                $jsonData['error'] = -5;
                $jsonData['error_note'] = 'Invoice not found';
                return $jsonData;
            }
            if ($giftCard->status != GiftCard::STATUS_NEW) {
                $jsonData['error'] = -4;
                $jsonData['error_note'] = 'Инвойс уже оплачен, повторная оплата невозможна';
                return $jsonData;
            }
            if (bccomp($giftCard->amount, $params['amount'], 2) !== 0) {
                $jsonData['error'] = -2;
                $jsonData['error_note'] = 'Wrong amount';
                return $jsonData;
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                $giftCard->status = GiftCard::STATUS_PAID;
                $giftCard->paid_at = $params['sign_time'];
                if (!$giftCard->save()) {
                    ComponentContainer::getErrorLogger()->logError('api/click', $giftCard->getErrorsAsString(), true);
                    $jsonData['error'] = -7;
                    $jsonData['error_note'] = 'Ошибка регистрации оплаты';
                    return $jsonData;
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
                $jsonData['message'] = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                ComponentContainer::getErrorLogger()->logError('api/paymo', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                return $jsonData;
            }

            return [
                'click_trans_id' => $params['click_trans_id'],
                'merchant_trans_id' => $params['merchant_trans_id'],
                'merchant_confirm_id' => null,
                'error' => 0,
            ];
        } else {
            /** @var Contract $contract */
            $contract = Contract::findOne(['number' => $params['merchant_trans_id']]);

            if (!$contract) {
                $jsonData['error'] = -5;
                $jsonData['error_note'] = 'Invoice not found';
                return $jsonData;
            }
            
            if ($contract->status == Contract::STATUS_PAID) {
                $jsonData['error'] = -4;
                $jsonData['error_note'] = 'Договор уже оплачен!';
                return $jsonData;
            }
            
            if (bccomp($contract->amount, $params['amount'], 2) !== 0) {
                $jsonData['error'] = -2;
                $jsonData['error_note'] = 'Wrong amount';
                return $jsonData;
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                $paymentId = MoneyComponent::payContract(
                    $contract,
                    new \DateTime($params['sign_time']),
                    Contract::PAYMENT_TYPE_CLICK,
                    $params['click_paydoc_id']
                );

                $contract->external_id = $params['click_trans_id'];
                $contract->save();

                $transaction->commit();
            } catch (\Throwable $exception) {
                $transaction->rollBack();
                $jsonData['error'] = -7;
                $jsonData['error_note'] = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                ComponentContainer::getErrorLogger()->logError('api/click', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                return $jsonData;
            }

            return [
                'click_trans_id' => $params['click_trans_id'],
                'merchant_trans_id' => $params['merchant_trans_id'],
                'merchant_confirm_id' => $paymentId,
                'error' => 0,
            ];
        }
    }
}
