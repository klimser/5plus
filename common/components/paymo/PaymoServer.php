<?php

namespace common\components\paymo;

use common\components\ComponentContainer;
use common\components\MoneyComponent;
use common\models\Contract;
use common\models\GiftCard;
use Yii;

class PaymoServer
{
    public function processPaymoRequest(): array
    {
        $jsonData = ['status' => 0];
        $whiteList = ['185.8.212.47', '185.8.212.48'];

        if (!Yii::$app->request->isPost) {
            $jsonData['message'] = 'Request should be POST';
            return $jsonData;
        }
        if (!in_array(Yii::$app->request->remoteIP, $whiteList)) {
            $jsonData['message'] = 'Wrong server IP';
            return $jsonData;
        }

        $params = json_decode(Yii::$app->request->rawBody, true);

        if ($params === false
            || !array_key_exists('store_id', $params)
            || !array_key_exists('transaction_id', $params)
            || !array_key_exists('invoice', $params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('sign', $params)) {
            $jsonData['message'] = 'Invalid JSON-body';
            return $jsonData;
        }

        $hash = $params['store_id'] . $params['transaction_id'] . $params['invoice'] . $params['amount'] . ComponentContainer::getPaymoApi()->apiKey;
        $sign = md5($hash);

        if ($sign != $params['sign']) {
            $jsonData['message'] = 'Invalid signature';
            return $jsonData;
        }

        if (preg_match('#^gc-(\d+)$#', $params['invoice'], $matches)) {
            /** @var GiftCard $giftCard */
            $giftCard = GiftCard::findOne(intval($matches[1]));

            if (!$giftCard) {
                $jsonData['message'] = 'Invoice not found';
                return $jsonData;
            }
            if ($giftCard->status != GiftCard::STATUS_NEW) {
                $jsonData['message'] = 'Инвойс уже оплачен, повторная оплата невозможна';
                return $jsonData;
            }
            if ($giftCard->amount * 100 != $params['amount']) {
                $jsonData['message'] = 'Wrong amount';
                return $jsonData;
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                $giftCard->status = GiftCard::STATUS_PAID;
                $giftCard->paid_at = array_key_exists('transaction_time', $params) ? $params['transaction_time'] : date('Y-m-d H:i:s');
                if (!$giftCard->save()) {
                    ComponentContainer::getErrorLogger()->logError('api/paymo', $giftCard->getErrorsAsString(), true);
                    $jsonData['message'] = 'Внутренняя ошибка сервера';
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
        } else {
            /** @var Contract $contract */
            $contract = Contract::findOne(['number' => $params['invoice']]);

            if (!$contract) {
                $jsonData['message'] = 'Invoice not found';
                return $jsonData;
            }
            if ($contract->amount * 100 != $params['amount']) {
                $jsonData['message'] = 'Wrong amount';
                return $jsonData;
            }

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
                return $jsonData;
            }
        }

        $jsonData['status'] = 1;
        $jsonData['message'] = 'Успешно';
        return $jsonData;
    }
}
