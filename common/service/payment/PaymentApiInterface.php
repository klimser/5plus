<?php

namespace common\service\payment;

interface PaymentApiInterface
{
    public function payCreate(float $amount, string $paymentId, ?string $returnUrl = null): string;
}
