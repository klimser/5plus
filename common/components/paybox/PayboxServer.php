<?php

namespace common\components\paybox;

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

class PayboxServer extends AbstractPaymentServer
{
    private function validateSignature(array $request, string $scriptName): bool
    {
        $sign = $request['pg_sig'] ?? '';
        unset($request['pg_sig']);
        $signedRequest = ComponentContainer::getPayboxApi()->signParams($request, $scriptName);

        return $sign === $signedRequest['pg_sig'];
    }

    public function handle(Request $request): Response
    {
        $url = rtrim($request->getUrl(), '/');
        $scriptName = ($pos = mb_strrpos($url, '/')) ? mb_substr($url, $pos + 1) : $url;
        $scriptName = strstr($scriptName, '?', true) ?: $scriptName;
        $response = new Response();
        $response->format = Response::FORMAT_XML;
        if (!$request->isPost) {
            $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'error', 'pg_description' => 'Request is not POST'], $scriptName);

            return $response;
        }

        $requestData = Yii::$app->request->post();

        if (!$requestData
            || !array_key_exists('pg_order_id', $requestData)
            || !array_key_exists('pg_payment_id', $requestData)
            || !array_key_exists('pg_result', $requestData)
            || !array_key_exists('pg_amount', $requestData)) {
            $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'error', 'pg_description' => 'Required request params missed'], $scriptName);

            return $response;
        }

        if (!$this->validateSignature($requestData, $scriptName)) {
            $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'error', 'pg_description' => 'Invalid signature'], $scriptName);

            return $response;
        }

        if (0 === (int) $requestData['pg_result']) {
            $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'ok', 'pg_description' => 'Payment was not done'], $scriptName);

            return $response;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            switch ($this->getTypeById($requestData['pg_order_id'])) {
                case Contract::class:
                    $contract = $this->getContractById($requestData['pg_order_id'], (int) $requestData['pg_amount']);
                    if ($contract->status != Contract::STATUS_PAID) {
                        MoneyComponent::payContract(
                            $contract,
                            new DateTime(),
                            $this->getPaymentTypeId(),
                            $requestData['pg_payment_id']
                        );
                        $contract->external_id = $requestData['pg_payment_id'];
                        if (!$contract->save()) {
                            throw new PayboxApiException($contract->getErrorsAsString());
                        }
                    }
                    $transaction->commit();

                    $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'ok'], $scriptName);

                    return $response;
                case GiftCard::class:
                    $giftCard = $this->getGiftCardById($requestData['pg_order_id'], (int) $requestData['pg_amount']);
                    if (!in_array($giftCard->status, [GiftCard::STATUS_PAID, GiftCard::STATUS_USED])) {
                        $giftCard->status = GiftCard::STATUS_PAID;
                        $giftCard->paid_at = date('Y-m-d H:i:s');
                        if (!$giftCard->save()) {
                            throw new PayboxApiException($giftCard->getErrorsAsString());
                        }

                        ComponentContainer::getMailQueue()->add(
                            'Квитанция об оплате',
                            $giftCard->customer_email,
                            'gift-card-html',
                            'gift-card-text',
                            ['id' => $giftCard->id]
                        );
                    }
                    $transaction->commit();

                    $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'ok'], $scriptName);

                    return $response;
            }

        } catch (PaymentServiceException $ex) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()->logError('api/paybox', $ex->getMessage() . "\n" . $ex->getTraceAsString(), true);

            if (1 === (int) $requestData['pg_can_reject']) {
                $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'rejected', 'pg_description' => 'Internal server error'], $scriptName);
            } else {
                $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'ok'], $scriptName);
            }

            return $response;
        }

        $response->data = ComponentContainer::getPayboxApi()->signParams(['pg_status' => 'error', 'pg_description' => 'Unknown error'], $scriptName);

        return $response;
    }

    public function getPaymentTypeId(): int
    {
        return Contract::PAYMENT_TYPE_PAYBOX;
    }
}
