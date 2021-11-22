<?php

namespace common\components\apelsin;

use yii\base\BaseObject;

/**
 * Class ApelsinApi
 * @package common\components
 * @property string $paymentUrl
 * @property string $cashId
 * @property string $login
 * @property string $password
 */
class ApelsinApi extends BaseObject
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
     * @param float $amount
     * @param string $paymentId
     * @param string|null $returnUrl
     * @return string
     */
    public function payCreate(float $amount, string $paymentId, ?string $returnUrl = null): string
    {
        return $this->paymentUrl . '?cash=' . $this->cashId . '&amount=' . round($amount * 100) . '&paymentId=' . $paymentId . '&redirectUrl=' . $returnUrl;
    }
}
