<?php

namespace common\components\AppPayme;

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
class AppPaymeApi extends BaseObject
{
    protected string $paymentUrl;
    protected int|string $merchantId;
    protected string $login;
    protected string $password;
    protected array $subjectMap;

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
     * @return array<string,int[]>
     */
    public function getSubjectMap(): array
    {
        return $this->subjectMap;
    }

    /**
     * @param array<string,int[]> $subjectMap
     */
    public function setSubjectMap(array $subjectMap): void
    {
        $this->subjectMap = $subjectMap;
    }
}
