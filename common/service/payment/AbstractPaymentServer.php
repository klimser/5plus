<?php

namespace common\service\payment;

use common\models\Contract;
use common\models\GiftCard;
use yii\web\Request;
use yii\web\Response;

abstract class AbstractPaymentServer implements PaymentServerInterface
{
    abstract public function handle(Request $request): Response;
    abstract public function getPaymentTypeId(): int;

    protected function getTypeById(string $id): string
    {
        if (preg_match('#^gc-(\d+)$#', $id, $matches)) {
            return GiftCard::class;
        }
        return Contract::class;
    }
    
    protected function getContractById(string $id, int $amount): Contract
    {
        /** @var Contract $contract */
        $contract = Contract::findOne(['number' => $id, 'payment_type' => $this->getPaymentTypeId()]);

        if (!$contract) {
            throw new PaymentServiceException('Invoice not found');
        }
        if ($contract->status == Contract::STATUS_PAID) {
            throw new PaymentServiceException('Invoice was already paid');
        }
        if ($contract->amount * 100 != $amount) {
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

        if (!$giftCard || !$giftCard->additionalData['payment_method'] || $this->getPaymentTypeId() !== $giftCard->additionalData['payment_method']) {
            throw new PaymentServiceException('Invoice not found');
        }
        if ($giftCard->status != GiftCard::STATUS_NEW) {
            throw new PaymentServiceException('Invoice was already paid');
        }
        if ((int) ($giftCard->amount * 100) !== $amount) {
            throw new PaymentServiceException('Wrong amount');
        }

        return $giftCard;
    }
}
