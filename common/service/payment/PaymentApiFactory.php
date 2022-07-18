<?php

namespace common\service\payment;

use common\components\ComponentContainer;
use common\models\Contract;

class PaymentApiFactory
{
    public static function getPaymentApi(int $paymentType): PaymentApiInterface
    {
        return match ($paymentType) {
            Contract::PAYMENT_TYPE_ATMOS => ComponentContainer::getPaymoApi(),
            Contract::PAYMENT_TYPE_CLICK => ComponentContainer::getClickApi(),
            Contract::PAYMENT_TYPE_PAYME => ComponentContainer::getPaymeApi(),
            Contract::PAYMENT_TYPE_APELSIN => ComponentContainer::getApelsinApi(),
            Contract::PAYMENT_TYPE_PAYBOX => ComponentContainer::getPayboxApi(),
            default => throw new PaymentServiceException('Unknown payment type passed'),
        };
    }
}