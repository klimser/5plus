<?php

namespace common\components\payme;

use common\service\payment\PaymentApiInterface;
use common\service\payment\TransactionResponse;
use yii\base\BaseObject;

/**
 * Class PaymeApi
 * @package common\components
 * @property string $paymentUrl
 * @property int|string $storeId
 * @property int|string $apiKey
 * @property string $login
 * @property string $password
 */
class PaymeApi extends BaseObject implements PaymentApiInterface
{
    /** @var string */
    protected $paymentUrl;
    /** @var int|string */
    protected $merchantId;
    /** @var string */
    protected $login;
    /** @var string */
    protected $password;

    /**
     * @return string
     */
    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    /**
     * @param string $paymentUrl
     */
    public function setPaymentUrl(string $paymentUrl)
    {
        $this->paymentUrl = $paymentUrl;
    }
    /**
     * @param mixed $merchantId
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
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
