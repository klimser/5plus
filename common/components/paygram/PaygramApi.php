<?php

namespace common\components\paygram;

use yii\base\BaseObject;

/**
 * Class PaygramApi
 * @package common\components
 */
class PaygramApi extends BaseObject
{
    const API_URL = 'https://api.paymo.uz';

    /** @var string */
    protected $login;
    /** @var string */
    protected $password;
    /** @var array */
    protected $templateMap;
    /** @var string */
    private $token;

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
     * @param array $templateMap
     */
    public function setTemplateMap(array $templateMap)
    {
        $this->templateMap = $templateMap;
    }

    /**
     * Выполнить запрос
     * @param string $urlAddon
     * @param bool $json
     * @param array $params
     * @param array $headers
     * @return mixed
     * @throws PaygramApiException
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
        } catch (\Throwable $ex) {
            throw new PaygramApiException($ex->getMessage(), $ex->getCode(), $ex);
        } finally {
            if (is_resource($curl)) {
                curl_close($curl);
            }
        }

        if (!$response) throw new PaygramApiException("Error: $err");

        $data = json_decode($response, true);
        if ($data === false) throw new PaygramApiException("Wrong response: $response");

        if (array_key_exists('result', $data)
            && array_key_exists('code', $data['result'])
            && $data['result']['code'] != 'OK') {
            throw new PaygramApiException($data['result']['description']);
        }

        return $data;
    }

    /**
     * @return string
     * @throws PaygramApiException
     */
    public function getToken(): string
    {
        if (!$this->token) {
            $response = $this->execute('/token', false, ['grant_type' => 'client_credentials'], ['Authorization: Basic ' . base64_encode("$this->login:$this->password")]);
            if ($response && array_key_exists('access_token', $response) && $response['access_token']) {
                $this->token = $response['access_token'];
            } else {
                throw new PaygramApiException('Unable to get access token');
            }
        }

        return $this->token;
    }

    /**
     * @param int $id
     * @param string $phone
     * @param array $params
     * @return bool
     * @throws PaygramApiException
     */
    public function sendSms(int $id, string $phone, array $params = []): bool
    {
        if (!array_key_exists($id, $this->templateMap)) {
            throw new PaygramApiException('Unknown template');
        }
        $response = $this->execute('/utils/sms/send', true, [
            'id' => $this->templateMap[$id],
            'phone' => $phone,
            'params' => $params,
        ]);

        if ((array_key_exists('status', $response) && $response['status'] === 'OK')
            || (array_key_exists('code', $response) && $response['code'] === 'OK')) {
            return true;
        }

        throw new PaygramApiException(print_r($response, true));
    }
}
