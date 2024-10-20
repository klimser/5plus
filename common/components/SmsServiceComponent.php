<?php

namespace common\components;

use yii\base\BaseObject;

class SmsServiceComponent extends BaseObject
{
    protected string $url;
    protected string $login;
    protected string $password;

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

    private function execute(string $method = 'GET', ?string $urlAddon = null, array $params = [], array $headers = []): array
    {
        $url = rtrim($this->url, '/');
        if ($urlAddon) {
            $url .= '/' . ltrim($urlAddon, '/');
        }
        $curl = curl_init($url);
        $headers[] = 'Content-Type: application/json';
        $curlOptions = [
            CURLOPT_USERPWD => "$this->login:$this->password",
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
        ];
        if (mb_strtoupper($method, 'UTF-8') === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
        }
        if (!empty($params)) {
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($params);
        }

        curl_setopt_array($curl, $curlOptions);

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

    public function sendSms(string $templateId, string $phone, array $params = []): bool
    {
        try {
            $response = $this->execute(
                'POST',
                null,
                [
                    'template_id' => $templateId,
                    'phone' => $phone,
                    'params' => $params,
                ],
            );

            if (empty($response)) {
                return true;
            }
        } catch (\Throwable $e) {
            ComponentContainer::getErrorLogger()->logError('sms/send', 'Error sending SMS: ' . $e->getMessage());

            return false;
        }

        return false;
    }
}