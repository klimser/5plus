<?php

namespace common\service\payment;

use yii\web\Request;
use yii\web\Response;

interface PaymentServerInterface
{
    public function handle(Request $request): Response;
    public function getPaymentTypeId(): int;
}
