<?php

namespace external\controllers;

use common\components\ComponentContainer;
use common\components\MoneyComponent;
use common\models\Contract;
use common\models\GiftCard;
use common\service\payment\PaymentServiceException;
use yii\web\Controller;
use yii\web\Response;

class PaymentController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        ComponentContainer::getExternalBasicAuth()->verify();

        return true;
    }

    protected function getTypeById(string $id): string
    {
        if (preg_match('#^gc-(\d+)$#', $id)) {
            return GiftCard::class;
        }
        return Contract::class;
    }

    protected function getContractById(string $id, int $amount): Contract
    {
        /** @var Contract $contract */
        $contract = Contract::findOne(['number' => $id]);

        if (!$contract) {
            throw new PaymentServiceException('Invoice not found');
        }
        if ($contract->status == Contract::STATUS_PAID) {
            throw new PaymentServiceException('Invoice was already paid');
        }
        if ($contract->amount != $amount) {
            throw new PaymentServiceException('Wrong amount');
        }

        return $contract;
    }

    protected function getGiftCardById(string $id, int $amount): GiftCard
    {
        if (!preg_match('#^gc-(\d+)$#', $id, $matches)) {
            throw new PaymentServiceException('Invoice not found');
        }

        /** @var GiftCard $giftCard */
        $giftCard = GiftCard::findOne((int) $matches[1]);

        if (!$giftCard) {
            throw new PaymentServiceException('Invoice not found');
        }
        if ($giftCard->status != GiftCard::STATUS_NEW) {
            throw new PaymentServiceException('Invoice was already paid');
        }
        if ($giftCard->amount != $amount) {
            throw new PaymentServiceException('Wrong amount');
        }

        return $giftCard;
    }

    public function actionRegister()
    {
        $request = \Yii::$app->request;
        $response = new Response();
        $response->format = Response::FORMAT_JSON;

        if (!$request->isPost) {
            $response->setStatusCode(400);
            $response->data = 'Request should be POST';
            return $response;
        }

        $params = json_decode($request->rawBody, true);
        if (empty($params)
            || !array_key_exists('number', $params)
            || !array_key_exists('provider', $params)
            || !array_key_exists('amount', $params)
            || !array_key_exists('external_id', $params)
            || !array_key_exists('parameters', $params)
            || !array_key_exists('paid_at', $params)) {
            $response->setStatusCode(400);
            $response->data = 'Invalid JSON-body';
            return $response;
        }

        try {
            switch ($this->getTypeById($params['number'])) {
                case Contract::class:
                    $contract = $this->getContractById($params['number'], $params['amount']);

                    $transaction = \Yii::$app->db->beginTransaction();
                    try {
                        $contract->external_id = $params['external_id'];
                        MoneyComponent::payContract(
                            $contract,
                            new \DateTime($params['paid_at'] ?: 'now'),
                            $params['provider'],
                            $params['external_id'],
                        );
                        $transaction->commit();
                    } catch (\Throwable $exception) {
                        $transaction->rollBack();
                        ComponentContainer::getErrorLogger()->logError('external/payment', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                        $response->setStatusCode(500);
                        $response->data = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                        return $response;
                    }
                    break;
                case GiftCard::class:
                    $giftCard = $this->getGiftCardById($params['number'], $params['amount']);

                    $transaction = \Yii::$app->db->beginTransaction();
                    try {
                        if (!empty($params['external_id'])) {
                            $additionalData = $giftCard->additionalData;
                            $additionalData['transaction_id'] = $params['external_id'];
                            $giftCard->additionalData = $additionalData;
                        }
                        $giftCard->status = GiftCard::STATUS_PAID;
                        $giftCard->paid_at = (new \DateTime($params['paid_at']))->format('Y-m-d H:i:s');
                        if (!$giftCard->save()) {
                            $transaction->rollBack();
                            ComponentContainer::getErrorLogger()->logError('external/payment', $giftCard->getErrorsAsString(), true);
                            $response->setStatusCode(500);
                            $response->data = 'Внутренняя ошибка сервера';
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
                        ComponentContainer::getErrorLogger()->logError('external/payment', $exception->getMessage() . "\n" . $exception->getTraceAsString(), true);
                        $response->setStatusCode(500);
                        $response->data = 'Ошибка регистрации оплаты: ' . $exception->getMessage();
                        return $response;
                    }
                    break;
            }
        } catch (PaymentServiceException $ex) {
            $response->setStatusCode(500);
            $response->data = $ex->getMessage();
            return $response;
        }

        $response->data = 'OK';
        return $response;
    }
}
