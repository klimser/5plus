<?php

namespace common\components\apelsin;

use common\components\ComponentContainer;
use common\components\MoneyComponent;
use common\models\Contract;
use common\models\GiftCard;
use common\service\payment\AbstractPaymentServer;
use common\service\payment\PaymentServiceException;
use DateTime;
use Yii;
use yii\web\Request;
use yii\web\Response;

class ApelsinServer extends AbstractPaymentServer
{
    public function handle(Request $request): Response
    {
        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        if (!$request->isPost) {
            $response->data = ['status' => false];
            return $response;
        }
        
        $requestData = json_decode(Yii::$app->request->rawBody, true);
        
        if (!$requestData
            || !array_key_exists('transactionId', $requestData)
            || !array_key_exists('amount', $requestData)
            || !array_key_exists('paymentId', $requestData)) {
            $response->data = ['status' => false];
            return $response;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $authComplete = false;
            $auth = Yii::$app->request->getHeaders()->get('Authorization', '');
            if ($auth) {
                [$devNull, $auth] = explode(' ', trim($auth), 2);
                $auth = base64_decode($auth);
                [$login, $password] = explode(':', $auth, 2);
                if ($login === ComponentContainer::getApelsinApi()->login && $password === ComponentContainer::getApelsinApi()->password) {
                    $authComplete = true;
                }
            }
            if (!$authComplete) {
                throw new ApelsinApiException('Authorization failed');
            }

            switch ($this->getTypeById($requestData['paymentId'])) {
                case Contract::class:
                    $contract = $this->getContractById($requestData['paymentId'], (int) $requestData['amount']);
                    MoneyComponent::payContract(
                        $contract,
                        new DateTime(),
                        $this->getPaymentTypeId(),
                        $requestData['transactionId']
                    );
                    $contract->external_id = $requestData['transactionId'];
                    if (!$contract->save()) {
                        throw new ApelsinApiException($contract->getErrorsAsString());
                    }
                    $transaction->commit();

                    $response->data = ['status' => true];
                    return $response;
                case GiftCard::class:
                    $giftCard = $this->getGiftCardById($requestData['paymentId'], (int) $requestData['amount']);
                    $giftCard->status = GiftCard::STATUS_PAID;
                    $giftCard->paid_at = date('Y-m-d H:i:s');
                    if (!$giftCard->save()) {
                        throw new ApelsinApiException($giftCard->getErrorsAsString());
                    }

                    ComponentContainer::getMailQueue()->add(
                        'Квитанция об оплате',
                        $giftCard->customer_email,
                        'gift-card-html',
                        'gift-card-text',
                        ['id' => $giftCard->id]
                    );
                    $transaction->commit();

                    $response->data = ['status' => true];
                    return $response;
            }
            
        } catch (PaymentServiceException $ex) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()->logError('api/apelsin', $ex->getMessage() . "\n" . $ex->getTraceAsString(), true);
            $response->data = ['status' => false];
            return $response;
        }

        $response->data = ['status' => false];
        return $response;
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_APELSIN;
    }
}
