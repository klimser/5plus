<?php

namespace common\components\payme;

use common\service\payment\PaymentApiInterface;
use common\service\payment\TransactionResponse;
use yii\base\BaseObject;

/**
 * Class PaymeApi
 * @package common\components
 * @property string $paymentUrl
 * @property int|string $merchantId
 * @property string $login
 * @property string $password
 */
class PaymeApi extends BaseObject implements PaymentApiInterface
{
    protected string $paymentUrl;
    protected int|string $merchantId;
    protected string $login;
    protected string $password;

    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    public function setPaymentUrl(string $paymentUrl)
    {
        $this->paymentUrl = $paymentUrl;
    }

    public function setMerchantId(int|string $merchantId)
    {
        $this->merchantId = $merchantId;
    }

    public function getMerchantId(): int|string
    {
        return $this->merchantId;
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
            $this->paymentUrl . '/' . base64_encode(
        "m={$this->merchantId};ac.order_id={$paymentId};a=" . round($amount * 100) . ';' . ($returnUrl ? "c={$returnUrl};" : '') . 'cr=860'
            ),
            []
        );
    }
}
