<?php

namespace common\components\SmsBroker;

use CurlHandle;
use InvalidArgumentException;
use Throwable;
use yii\base\BaseObject;

class SmsBrokerApi extends BaseObject
{
    protected string $baseUrl = '';
    protected string $login = '';
    protected string $password = '';
    protected string $sender = '';

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @throws SmsBrokerApiException
     */
    public function sendSingleMessage(string $recipientPhone, string $content, string $messageId, ?string $from = null): mixed
    {
        $recipientPhone = preg_replace('#\D#', '', $recipientPhone);
        if (12 !== strlen($recipientPhone)) {
            throw new InvalidArgumentException('Invalid recipient phone');
        }

        $message = [
            'recipient' => $recipientPhone,
            'sms' => [
                'content' => [
                    'text' => $content,
                ],
                'originator' => $from ?? $this->sender,
            ],
            'message-id' => $messageId,
        ];
        return $this->execute('/send', ['messages' => [$message]]);
    }

    /**
     * @param array<string,mixed>  $params
     * @param array<string>  $headers
     *
     * @throws SmsBrokerApiException
     */
    private function execute(string $urlAddon, array $params = [], array $headers = []): mixed
    {
        $curl = curl_init($this->baseUrl . $urlAddon);
        $headers[] = 'Content-Type: application/json; charset=utf-8';

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->login . ':' . $this->password,
        ]);

        try {
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        } catch (Throwable $ex) {
            throw new SmsBrokerApiException($ex->getMessage(), $ex->getCode(), $ex);
        } finally {
            if ($curl instanceof CurlHandle) {
                curl_close($curl);
            }
        }

        if (!$response) throw new SmsBrokerApiException("Error: $err");

        $data = json_decode($response, true);
        if ($data === false) throw new SmsBrokerApiException("Wrong response: $response");

        if (200 !== $code) {
            throw new SmsBrokerApiException('Error ' . ($data['error-code'] ?? 'unknown') . ': ' . ($data['error-description'] ?? 'unknown'));
        }

        return $data;
    }
}
