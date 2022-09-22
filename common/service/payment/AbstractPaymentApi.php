<?php

namespace common\service\payment;

abstract class AbstractPaymentApi implements PaymentApiInterface
{
    abstract public function payCreate(float $amount, string $paymentId, ?string $returnUrl = null, array $details = []): TransactionResponse;
}
