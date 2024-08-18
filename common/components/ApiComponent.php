<?php

namespace common\components;

use yii\base\BaseObject;
use yii\helpers\Url;

class ApiComponent extends BaseObject
{
    protected string $url;
    protected string $login;
    protected string $password;
    private string $token;

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getToken(): string
    {
        if (empty($this->token)) {
            $response = $this->execute(
                'token',
                ['username' => $this->login, 'password' => $this->password],
            );
            if ($response && array_key_exists('token', $response) && $response['token']) {
                $this->token = $response['token'];
            } else {
                throw new \Exception('Unable to get access token');
            }
        }

        return $this->token;
    }

    private function execute(string $urlAddon, array $params = [], array $headers = []): array
    {
        $curl = curl_init(rtrim($this->url, '/') . '/' . ltrim($urlAddon, '/'));
        $postParams = json_encode($params);
        $headers[] = 'Content-Type: application/json';
        if ('token' !== $urlAddon) {
            $headers[] = 'Authorization: Bearer ' . $this->getToken();
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
            $errNo = curl_errno($curl);
            $errText = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } finally {
            if (is_resource($curl) || $curl instanceof \CurlHandle) {
                curl_close($curl);
            }
        }

        if (0 !== $errNo) throw new \Exception("Error: $errText");

        switch ($httpCode) {
            case 204:
                return [];
            case 200:
                $data = json_decode($response, true);
                if ($data === false) throw new \Exception("Wrong response: $response");

                return $data;
        }

        throw new \Exception("Wrong response code: $httpCode, response - $response");
    }

    public function createPayment(int $studentId, int $courseId, int $amount, int $providerId): string
    {
        $response = $this->execute(
            'payment/create',
            [
                "user_id" => $studentId,
                  "course_id" => $courseId,
                  "payment_provider" => $providerId,
                  "amount" => $amount,
                  "return_url" => Url::to(['payment/complete', 'payment' => '{contractId}'], true)
            ],
        );

        switch ($response['status']) {
            case 'ok':
                return $response['redirect_url'];
            default:
                throw new \Exception($response['error'] ?? 'Unknown error');
        }
    }

    public function createGiftCardPayment(array $giftCardData, int $providerId): string
    {
        $requestParams = [
            "gift_card_id" => intval($giftCardData['type']),
            "student_name" => $giftCardData['student_name'],
            "student_phone" => $giftCardData['student_phone'],
            "email" => $giftCardData['email'],
            "payment_provider" => $providerId,
            "return_url" => Url::to(['payment/complete', 'gc' => '{giftCardCode}'], true)
        ];

        if (!empty($giftCardData['parents_name'])) {
            $requestParams['parent_name'] = $giftCardData['parents_name'];
        }
        if (!empty($giftCardData['parents_phone'])) {
            $requestParams['parent_phone'] = $giftCardData['parents_phone'];
        }

        $response = $this->execute(
            'payment/gift-card',
            $requestParams,
        );

        switch ($response['status']) {
            case 'ok':
                return $response['redirect_url'];
            default:
                throw new \Exception($response['error'] ?? 'Unknown error');
        }
    }
}