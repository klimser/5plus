<?php

namespace common\components\paymo;

use yii\base\BaseObject;

/**
 * Class PaymoApi
 * @package common\components
 * @property string $storeId
 */
class PaymoApi extends BaseObject
{
    const API_URL = 'https://api.pays.uz:8243';

    protected $storeId;
    protected $apiKey;

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
    public function setApiKey($apiKey)
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
     * Выполнить запрос
     * @param string $urlAddon
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    private function execute(string $urlAddon, array $params = [])
    {
        $curl = curl_init(self::API_URL . $urlAddon);
        $params['lang'] = 'ru';
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
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

        if (!$response) throw new PaymoApiException("Paymo API error: $err");

        $data = json_decode($response, true);
        if ($data === false) throw new PaymoApiException("Paymo API wrong response: $response");

        if (array_key_exists('result', $data)
            && array_key_exists('code', $data['result'])
            && $data['result']['code'] != 'OK') {
            throw new PaymoApiException($data['result']['description']);
        }

        return $data;
    }

    /**
     * @param int $amount
     * @param string $paymentId
     * @param array $details
     * @return mixed
     * @throws PaymoApiException
     */
    public function payCreate(int $amount, string $paymentId, array $details = [])
    {
        $response = $this->execute('/merchant/pay/create', [
            'amount' => $amount,
            'account' => $paymentId,
            'store_id' => $this->storeId,
            'details' => $details,
        ]);

        if (array_key_exists('transaction_id', $response)) return $response['transaction_id'];

        throw new PaymoApiException('Paymo API wrong response');
    }
}