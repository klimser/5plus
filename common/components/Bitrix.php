<?php

namespace common\components;

use Psr\Log\LoggerInterface;
use yii\base\BaseObject;

class Bitrix extends BaseObject
{
    /** @var string */
    protected $apiKey;
    /** @var string */
    protected $domain;
    /** @var int */
    protected $userId;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param mixed $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $method
     * @param array $params
     * @return array|mixed
     */
    public function call(string $method, array $params = [])
    {
        $url = "https://$this->domain/rest/$this->userId/$this->apiKey/$method.json";

        $curlOptions = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_CONNECTTIMEOUT => 25,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_URL => $url,
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);

        $result = [];
        try {
            $curlResult = curl_exec($curl);
            if (false === $curlResult) {
                $errorMsg = sprintf(' cURL error (code %s): %s' . PHP_EOL, curl_errno($curl), curl_error($curl));
                $this->logger->error($errorMsg, ['method' => $method, 'params' => $params]);
            }
            curl_close($curl);

            if (!empty($curlResult)) {
                $jsonData = json_decode($curlResult, true);
                $result = array_key_exists('result', $jsonData) ? $jsonData['result'] : $jsonData;
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['method' => $method, 'params' => $params]);
            if (is_resource($curl)) curl_close($curl);
        }

        return $result;
    }
}
