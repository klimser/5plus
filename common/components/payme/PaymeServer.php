<?php

namespace common\components\payme;

use common\components\ComponentContainer;
use common\components\MoneyComponent;
use common\models\Contract;
use common\models\GiftCard;
use common\service\payment\AbstractPaymentServer;
use common\service\payment\PaymentServiceException;
use Yii;
use yii\web\Request;
use yii\web\Response;

class PaymeServer extends AbstractPaymentServer
{
    public function handle(Request $request): Response
    {
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        if (!$request->isPost) {
            $response->data = ['id' => 0, 'error' => ['code' => -32300, 'message' => ['en' => 'Request should be POST']]];
            return $response;
        }
        
        $requestData = json_decode($request->rawBody, true);
        
        if (!$requestData) {
            $response->data =  ['id' => 0, 'error' => ['code' => -32700, 'message' => ['en' => 'Failed to parse JSON']]];
            return $response;
        }

        try {
            $authComplete = false;
            $auth = $request->getHeaders()->get('Authorization', '');
            if ($auth) {
                [$devNull, $auth] = explode(' ', trim($auth), 2);
                $auth = base64_decode($auth);
                [$login, $password] = explode(':', $auth, 2);
                if ($login === ComponentContainer::getPaymeApi()->login && $password === ComponentContainer::getPaymeApi()->password) {
                    $authComplete = true;
                }
            }
            if (!$authComplete) {
                throw new PaymeApiException('Authorization failed', -32504);
            }
            
            switch ($requestData['method']) {
                case 'CheckPerformTransaction':
                    $responseData = $this->checkPerformTransaction($requestData['params']);
                    break;
                case 'CreateTransaction':
                    $responseData = $this->createTransaction($requestData['params']);
                    break;
                case 'PerformTransaction':
                    $responseData = $this->complete($requestData['params']);
                    break;
                case 'CancelTransaction':
                    $responseData = $this->cancel($requestData['params']);
                    break;
                case 'CheckTransaction':
                    $responseData = $this->get($requestData['params']);
                    break;
                case 'GetStatement':
                    $responseData = $this->history($requestData['params']);
                    break;
                case 'ChangePassword':
                    throw new PaymeApiException('Password is immutable', -32400);
                default:
                    $responseData = ['error' => ['code' => -32601, 'message' => ['en' => 'Method does not exists']]];
            }
        } catch (PaymentServiceException $ex) {
            $responseData = ['error' => ['code' => $ex->getCode(), 'message' => $ex->getMessage()]];
        }
        
        $responseData['id'] = $requestData['id'];
        $response->data =  $responseData;
        return $response;
    }
    
    private function checkPerformTransaction($params): array
    {
        if (empty($params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('account', $params)
            || !isset($params['account']['order_id'])) {
            throw new PaymeApiException('Invalid request data', -31050);
        }

        switch ($this->getTypeById($params['account']['order_id'])) {
            case Contract::class:
                if ($this->getContractById($params['account']['order_id'], (int) $params['amount'])) {
                    return ['result' => ['allow' => true]];
                }
                break;
            case GiftCard::class:
                if ($this->getGiftCardById($params['account']['order_id'], (int) $params['amount'])) {
                    return ['result' => ['allow' => true]];
                }
                break;
        }

        throw new PaymeApiException('Invoice not found', -31050);
    }

    private function createTransaction($params): array
    {
        if (empty($params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('account', $params)
            || !isset($params['account']['order_id'])) {
            throw new PaymeApiException('Invalid request data', -31050);
        }

        $transactionTime = 0;
        switch ($this->getTypeById($params['account']['order_id'])) {
            case Contract::class:
                if ($contract = $this->getContractById($params['account']['order_id'], (int) $params['amount'])) {
                    if ($contract->external_id) {
                        [$id, $time] = explode('|', $contract->external_id);
                        if ($id !== $params['id']) {
                            throw new PaymeApiException('Another transaction was already started', -31057);
                        }
                        $transactionTime = (int)$time;
                    } else {
                        $contract->payment_type = $this->getPaymentTypeId();
                        if (!$transactionTime) {
                            $contract->external_id = $params['id'] . '|' . $params['time'];
                            $transactionTime = $params['time'];
                        }
                    }
                    $contract->save();
                    return ['result' => ['create_time' => $transactionTime, 'transaction' => (string)$contract->id, 'state' => 1]];
                }
                break;
            case GiftCard::class:
                if ($giftCard = $this->getGiftCardById($params['account']['order_id'], (int) $params['amount'])) {
                    $data = $giftCard->additionalData;
                    if (isset($data['payme_transaction_id'])) {
                        if($data['payme_transaction_id'] !== $params['id']) {
                            throw new PaymeApiException('Another transaction was already started', -31057);
                        }
                        $transactionTime = (int)$data['payme_transaction_time'];
                    } else {
                        $data['payme_transaction_id'] = $params['id'];
                        $data['payme_transaction_time'] = $params['time'];
                        $giftCard->additionalData = $data;
                        $giftCard->save();
                        $transactionTime = $params['time'];
                    }
                    return ['result' => ['create_time' => $transactionTime, 'transaction' => (string)$giftCard->id, 'state' => 1]];
                }
                break;
        }

        throw new PaymeApiException('Invalid request data', -31050);
    }

    private function complete($params): array
    {
        if (empty($params) || !array_key_exists('id', $params)) {
            throw new PaymeApiException('Invalid request data', -31050);
        }
        
        /** @var Contract $contract */
        if ($contract = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_PAYME])
            ->andWhere(['like', 'external_id', $params['id'] . '|%', false])->one()) {
            if ($contract->status != Contract::STATUS_PAID) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    MoneyComponent::payContract(
                        $contract,
                        new \DateTime('now'),
                        Contract::PAYMENT_TYPE_PAYME,
                        $params['id']
                    );
                    $transaction->commit();
                } catch (\Throwable $exception) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()->logError('api/payme', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                    throw new PaymeApiException('Internal server error', -31008);
                }
            }
            return ['result' => ['transaction' => (string)$contract->id, 'perform_time' => $contract->paidDate->getTimestamp() * 1000, 'state' => 2]];
        }

        /** @var GiftCard $giftCard */
        if ($giftCard = GiftCard::find()->andWhere(['like', 'additional', '"payme_transaction_id":"' . $params['id'] . '"'])->one()) {
            if ($giftCard->status == GiftCard::STATUS_NEW) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $giftCard->status = GiftCard::STATUS_PAID;
                    $giftCard->paid_at = array_key_exists('transaction_time', $params) ? $params['transaction_time'] : date('Y-m-d H:i:s');
                    if (!$giftCard->save()) {
                        ComponentContainer::getErrorLogger()->logError('api/payme', $giftCard->getErrorsAsString(), true);
                        throw new PaymeApiException('Internal server error', -31008);
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
                    ComponentContainer::getErrorLogger()->logError('api/payme', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                    throw new PaymeApiException('Internal server error', -31008);
                }
            }
            return ['result' => ['transaction' => (string)$giftCard->id, 'perform_time' => $giftCard->paidDate->getTimestamp() * 1000, 'state' => 2]];
        }

        throw new PaymeApiException('Transaction not found', -31003);
    }

    private function cancel($params): array
    {
        if (empty($params) || !array_key_exists('id', $params)) {
            throw new PaymeApiException('Invalid request data', -31050);
        }

        /** @var Contract $contract */
        if ($contract = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_PAYME])
            ->andWhere(['like', 'external_id', $params['id'] . '|%', false])->one()) {
            if ($contract->status == Contract::STATUS_PAID) {
                throw new PaymeApiException('Transaction is not allowed to cancel', -31007);
            }
            return ['result' => ['transaction' => (string)$contract->id, 'cancel_time' => $contract->createDate->getTimestamp() * 1000, 'state' => -2]];
        }

        /** @var GiftCard $giftCard */
        if ($giftCard = GiftCard::find()->andWhere(['like', 'additional', '"payme_transaction_id":"' . $params['id'] . '"'])->one()) {
            if ($giftCard->status != GiftCard::STATUS_NEW) {
                throw new PaymeApiException('Transaction is not allowed to cancel', -31007);
            }
            return ['result' => ['transaction' => (string)$giftCard->id, 'perform_time' => $giftCard->createDate->getTimestamp() * 1000, 'state' => 2]];
        }

        throw new PaymeApiException('Transaction not found', -31003);
    }

    private function get($params): array
    {
        if (empty($params) || !array_key_exists('id', $params)) {
            throw new PaymeApiException('Invalid request data', -31050);
        }

        /** @var Contract $contract */
        if ($contract = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_PAYME])
            ->andWhere(['like', 'external_id', $params['id'] . '|%', false])->one()) {
            [$id, $time] = explode('|', $contract->external_id);
            return ['result' => [
                'create_time' => (int)$time,
                'transaction' => (string)$contract->id,
                'state' => $contract->status == Contract::STATUS_PAID ? 2 : 1,
                'perform_time' => $contract->status == Contract::STATUS_PAID ? $contract->paidDate->getTimestamp() * 1000 : 0,
                'cancel_time' => 0,
                'reason' => null,
            ]];
        }

        /** @var GiftCard $giftCard */
        if ($giftCard = GiftCard::find()->andWhere(['like', 'additional', '"payme_transaction_id":"' . $params['id'] . '"'])->one()) {
            return ['result' => [
                'create_time' => (int)$giftCard->additionalData['payme_transaction_time'],
                'transaction' => (string)$giftCard->id,
                'state' => $giftCard->status == GiftCard::STATUS_PAID ? 2 : 1,
                'perform_time' => $giftCard->status == GiftCard::STATUS_PAID ? $giftCard->paidDate->getTimestamp() * 1000 : 0,
                'cancel_time' => 0,
                'reason' => null,
            ]];
        }

        throw new PaymeApiException('Transaction not found', -31003);
    }
    
    private function history($params)
    {
        if (empty($params) || !array_key_exists('from', $params) || !array_key_exists('to', $params)) {
            throw new PaymeApiException('Invalid request data', -31050);
        }
        
        $startDate = new \DateTime();
        $startDate->setTimestamp($params['from']);
        $startDate->modify('-2 days');
        $endDate = new \DateTime();
        $endDate->setTimestamp($params['to']);
        $endDate->modify('+2 days');
        
        $results = [];
        
        /** @var Contract[] $contracts */
        $contracts = Contract::find()
            ->andWhere(['payment_type' => Contract::PAYMENT_TYPE_PAYME])
            ->andWhere(['between', 'created_at', $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->andWhere(['not', ['external_id' => null]])
            ->all();
        foreach ($contracts as $contract) {
            [$transactionId, $timestamp] = explode('|', $contract->external_id);
            if ($timestamp >= $params['from'] && $timestamp <= $params['to']) {
                $results[$timestamp . '-c-' . $contract->id] = [
                    'id' => $transactionId,
                    'time' => (int)$timestamp,
                    'amount' => $contract->amount * 100,
                    'account' => ['order_id' => $contract->number],
                    'create_time' => (int)$timestamp,
                    'perform_time' => $contract->status == Contract::STATUS_PAID ? $contract->paidDate->getTimestamp() * 1000 : 0,
                    'cancel_time' => 0,
                    'transaction' => (string)$contract->id,
                    'state' => $contract->status == Contract::STATUS_PAID ? 2 : 1,
                ];
            }
        }
        
        /** @var GiftCard[] $giftCards */
        $giftCards = GiftCard::find()
            ->andWhere(['like', 'additional', '"payme_transaction_id":'])
            ->andWhere(['between', 'created_at', $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->all();
        foreach ($giftCards as $giftCard) {
            if ($giftCard->additionalData['payme_transaction_time'] >= $params['from'] && $giftCard->additionalData['payme_transaction_time'] <= $params['to']) {
                $results[$giftCard->additionalData['payme_transaction_time'] . '-g-' . $giftCard->id] = [
                    'id' => $giftCard->additionalData['payme_transaction_id'],
                    'time' => (int)$giftCard->additionalData['payme_transaction_time'],
                    'amount' => $giftCard->amount * 100,
                    'account' => ['order_id' => 'gc-' . $giftCard->id],
                    'create_time' => (int)$giftCard->additionalData['payme_transaction_time'],
                    'perform_time' => $giftCard->status == GiftCard::STATUS_PAID ? $giftCard->paidDate->getTimestamp() * 1000 : 0,
                    'cancel_time' => 0,
                    'transaction' => (string)$giftCard->id,
                    'state' => $giftCard->status == Contract::STATUS_PAID ? 2 : 1,
                ];
            }
        }
        
        ksort($results);
        return ['result' => ['transactions' => array_values($results)]]; 
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_PAYME;
    }
}
