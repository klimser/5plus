<?php

namespace common\components;

use Tinify\Source;
use yii\base\BaseObject;

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
     * @param mixed $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
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
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $data = json_decode($response, true);
        if (array_key_exists('result', $data)
            && array_key_exists('code', $data['result'])
            && $data['result']['code'] != 'OK') {
            throw new \Exception($data['result']['description']);
        }

        return $data;
    }
}