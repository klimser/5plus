<?php

namespace common\components\apelsin;

use common\service\payment\PaymentApiInterface;
use common\service\payment\TransactionResponse;
use yii\base\BaseObject;

/**
 * Class ApelsinApi
 * @package common\components
 * @property string $paymentUrl
 * @property string $cashId
 * @property string $login
 * @property string $password
 */
class ApelsinApi extends BaseObject implements PaymentApiInterface
{
    protected string $paymentUrl;
    protected string $cashId;
    protected string $login;
    protected string $password;

    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    public function setPaymentUrl(string $paymentUrl): void
    {
        $this->paymentUrl = $paymentUrl;
    }

    public function getCashId(): string
    {
        return $this->cashId;
    }

    public function setCashId(string $cashId): void
    {
        $this->cashId = $cashId;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @param array<mixed> $details
     */
    public function payCreate(float $amount, string $paymentId, ?string $returnUrl = null, array $details = []): TransactionResponse
    {
        return new TransactionResponse(
            null,
            $this->paymentUrl . '?cash=' . $this->cashId . '&amount=' . round($amount * 100) . '&paymentId=' . $paymentId . '&redirectUrl=' . $returnUrl,
            []
        );
    }
}
