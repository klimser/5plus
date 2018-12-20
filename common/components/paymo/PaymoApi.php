<?php

namespace common\components\paymo;

use yii\base\BaseObject;

/**
 * Class PaymoApi
 * @package common\components
 * @property string $paymentUrl
 * @property int|string $storeId
 * @property int|string $apiKey
 */
class PaymoApi extends BaseObject
{
    const API_URL = 'https://api.pays.uz:8243';

    /** @var string */
    protected $paymentUrl;
    /** @var int|string */
    protected $storeId;
    /** @var int|string */
    protected $apiKey;
    /** @var string */
    protected $login;
    /** @var string */
    protected $password;
    /** @var string */
    private $token;

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
     * @param mixed $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param mixed $apiKey
     */
    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $login
     */
    public function setLogin(string $login)
    {
        $this->login = $login;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * Выполнить запрос
     * @param string $urlAddon
     * @param bool $json
     * @param array $params
     * @param array $headers
     * @return mixed
     * @throws PaymoApiException
     */
    private function execute(string $urlAddon, bool $json = true, array $params = [], array $headers = [])
    {
        $curl = curl_init(self::API_URL . $urlAddon);
        if ($json) {
            $params['lang'] = 'ru';
            $postParams = json_encode($params);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer ' . $this->getToken();
        } else {
            $postParams = http_build_query($params);
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postParams,
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
            throw new PaymoApiException($ex->getMessage(), $ex->getCode(), $ex);
        }

        if (!$response) throw new PaymoApiException("Error: $err");

        $data = json_decode($response, true);
        if ($data === false) throw new PaymoApiException("Wrong response: $response");

        if (array_key_exists('result', $data)
            && array_key_exists('code', $data['result'])
            && $data['result']['code'] != 'OK') {
            throw new PaymoApiException($data['result']['description']);
        }

        return $data;
    }

    /**
     * @return string
     * @throws PaymoApiException
     */
    public function getToken(): string
    {
        if (!$this->token) {
            $response = $this->execute('/token', false, ['grant_type' => 'client_credentials'], ['Authorization: Basic ' . base64_encode("$this->login:$this->password")]);
            if ($response && array_key_exists('access_token', $response) && $response['access_token']) {
                $this->token = $response['access_token'];
            } else {
                throw new PaymoApiException('Unable to get access token');
            }
        }

        return $this->token;
    }

    /**
     * @param int $amount
     * @param string $paymentId
     * @param array $details
     * @return string
     * @throws PaymoApiException
     */
    public function payCreate(int $amount, string $paymentId, array $details = []): string
    {
        $response = $this->execute('/merchant/pay/create', true, [
            'amount' => $amount,
            'account' => $paymentId,
            'store_id' => $this->storeId,
            'details' => json_encode($details),
        ]);

        if (array_key_exists('transaction_id', $response)) return strval($response['transaction_id']);

        throw new PaymoApiException('Wrong response: ' . print_r($response, true));
    }
}