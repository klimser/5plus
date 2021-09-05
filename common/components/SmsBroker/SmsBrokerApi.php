<?php

namespace common\components\SmsBroker;

class SmsBrokerApi
{
    /** @var string */
    protected $baseUrl;
    /** @var string */
    protected $login;
    /** @var string */
    protected $password;
    /** @var string */
    protected $sender;

    public function sendSingleMessage(string $recipientPhone, string $content, ?string $from = null, ?string $messageId = null)
    {
        $recipientPhone = preg_replace('\D', '', $recipientPhone);
        if (12 !== strlen($recipientPhone)) {
            throw new \InvalidArgumentException('Invalid recipient phone');
        }

        $sms = [
            'content' => [
                'text' => $content,
            ]
        ];
        $sms['originator'] = $from ?? $this->sender;
        $message = [
            'recipient' => $recipientPhone,
            'sms' => $sms,
        ];
        if ($messageId) {
            $message['message-id'] = $messageId;
        }
        $params = [
            'messages' => [$message]
        ];
        return $this->execute('/send', $params);
    }

    /**
     * Выполнить запрос
     * @param string $urlAddon
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    private function execute(string $urlAddon, array $params = [], array $headers = [])
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
            CURLOPT_USERPWD => "{$this->login}:{$this->password}",
        ]);

        try {
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        } catch (\Throwable $ex) {
            throw new SmsBrokerApiException($ex->getMessage(), $ex->getCode(), $ex);
        } finally {
            if (is_resource($curl)) {
                curl_close($curl);
            }
        }

        if (!$response) throw new SmsBrokerApiException("Error: $err");

        $data = json_decode($response, true);
        if ($data === false) throw new SmsBrokerApiException("Wrong response: $response");

        if (200 !== $code) {
            throw new SmsBrokerApiException("Error {$data['error_code']}: {$data['error_description']}");
        }

        return $data;
    }
}