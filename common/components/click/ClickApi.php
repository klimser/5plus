<?php

namespace common\components\click;

use yii\base\BaseObject;

/**
 * Class ClickApi
 * @package common\components
 * @property string $paymentUrl
 * @property int $merchantId
 * @property int $serviceId
 * @property int $merchantUserId
 * @property string $secretKey
 */
class ClickApi extends BaseObject
{
    const API_URL = 'https://api.click.uz/v2/merchant';

    protected string $paymentUrl;
    protected int $merchantId;
    protected int $serviceId;
    protected int $merchantUserId;
    protected string $secretKey;

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
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param mixed $merchantId
     */
    public function setMerchantId($merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    /**
     * @return mixed
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param mixed $serviceId
     */
    public function setServiceId($serviceId): void
    {
        $this->serviceId = $serviceId;
    }

    /**
     * @return mixed
     */
    public function getMerchantUserId()
    {
        return $this->merchantUserId;
    }

    /**
     * @param mixed $merchantUserId
     */
    public function setMerchantUserId($merchantUserId): void
    {
        $this->merchantUserId = $merchantUserId;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @param mixed $secretKey
     */
    public function setSecretKey($secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Выполнить запрос
     * @param string $urlAddon
     * @param bool $json
     * @param array $params
     * @param array $headers
     * @return mixed
     * @throws ClickApiException
     */
    private function execute(string $urlAddon, array $params = [], array $headers = [])
    {
        $curl = curl_init(self::API_URL . $urlAddon);
        $timestamp = time();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Auth: ' . $this->merchantUserId . ':' . sha1($timestamp . $this->secretKey) . ':' . $timestamp;
        
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        try {
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
        } catch (\Throwable $ex) {
            if (is_resource($curl)) curl_close($curl);
            throw new ClickApiException($ex->getMessage(), $ex->getCode(), $ex);
        }

        if (!$response) throw new ClickApiException("Error: $err");

        $data = json_decode($response, true);
        if ($data === false) throw new ClickApiException("Wrong response: $response");

        if (array_key_exists('result', $data)
            && array_key_exists('code', $data['result'])
            && $data['result']['code'] != 'OK') {
            throw new ClickApiException($data['result']['description']);
        }

        return $data;
    }

    /**
     * @param float $amount
     * @param string $paymentId
     * @return string
     */
    public function payCreate(float $amount, string $paymentId): string
    {
        return "$this->paymentUrl/services/pay?service_id=$this->serviceId&merchant_id=$this->merchantId&amount="
            . number_format(round($amount, 2), 2, '.', '') . "&transaction_param=$paymentId";
    }
}
