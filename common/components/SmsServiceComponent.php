<?php

namespace common\components;

use yii\base\BaseObject;

class SmsServiceComponent extends BaseObject
{
    public const MAX_PAGE_SIZE = 200;
    public const SINGLE_SMS_LENGTH = 160;
    public const MULTIPART_SMS_LENGTH = 153;
    /** Format required by SMS API for date_from / date_to: YYYY-MM-DD HH:MM:SS */
    public const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public const STATUS_NEW = 0;
    public const STATUS_QUEUED = 1;
    public const STATUS_SENT = 2;
    public const STATUS_ERROR = 3;

    public const STATUS_LABELS = [
        self::STATUS_NEW => 'Новое',
        self::STATUS_QUEUED => 'В очереди',
        self::STATUS_SENT => 'Отправлено',
        self::STATUS_ERROR => 'Ошибка',
    ];

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
        $isPost = mb_strtoupper($method, 'UTF-8') === 'POST';
        if (!$isPost && !empty($params)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
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
        if ($isPost) {
            $curlOptions[CURLOPT_POST] = true;
            if (!empty($params)) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($params);
            }
        }

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $errNo = curl_errno($curl);
        $errText = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (0 !== $errNo) throw new \Exception("Error: $errText");

        switch ($httpCode) {
            case 204:
                return [];
            case 200:
                $data = json_decode($response, true);
                if (null === $data && 'null' === trim((string)$response)) {
                    return [];
                }
                if (!is_array($data)) throw new \Exception("Wrong response: $response");

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

    /**
     * @return array<array{id: int, phone: string, content: string|null, status: int, error: string|null, created_at: \DateTimeImmutable|null}>
     * @throws \Exception
     */
    public function getMessages(
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        ?string $phone = null,
        int $page = 1,
        int $pageSize = self::MAX_PAGE_SIZE,
        ?string $password = null,
    ): array {
        $params = [
            'page' => max(1, $page),
            'page_size' => max(1, min(self::MAX_PAGE_SIZE, $pageSize)),
        ];
        if ($dateFrom) {
            $params['date_from'] = $dateFrom->format(self::DATE_TIME_FORMAT);
        }
        if ($dateTo) {
            $params['date_to'] = $dateTo->format(self::DATE_TIME_FORMAT);
        }
        if (null !== $phone && '' !== $phone) {
            $params['phone'] = $phone;
        }
        if (null !== $password && '' !== $password) {
            $params['password'] = $password;
        }

        $response = $this->execute('GET', null, $params);

        $messages = [];
        foreach ($response as $item) {
            if (!is_array($item)) {
                continue;
            }
            $content = null;
            if (isset($item['content']) && '' !== $item['content']) {
                $decoded = base64_decode($item['content'], true);
                $content = false === $decoded ? (string)$item['content'] : $decoded;
            }
            $createdAt = null;
            if (!empty($item['created_at'])) {
                try {
                    $createdAt = (new \DateTimeImmutable($item['created_at']))
                        ->setTimezone(new \DateTimeZone(\Yii::$app->timeZone));
                } catch (\Throwable) {
                }
            }
            $messages[] = [
                'id' => (int)($item['id'] ?? 0),
                'phone' => (string)($item['phone'] ?? ''),
                'content' => $content,
                'status' => (int)($item['status'] ?? 0),
                'error' => $item['error'] ?? null,
                'created_at' => $createdAt,
            ];
        }

        return $messages;
    }

    /**
     * Number of SMS parts: up to 160 characters for a single SMS, 153 characters per part for multipart.
     */
    public static function countSmsParts(string $content): int
    {
        $length = mb_strlen($content, 'UTF-8');
        if ($length <= self::SINGLE_SMS_LENGTH) {
            return 1;
        }

        return (int)ceil($length / self::MULTIPART_SMS_LENGTH);
    }
}
