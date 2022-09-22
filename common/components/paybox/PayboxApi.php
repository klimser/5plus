<?php

namespace common\components\paybox;

use common\components\helpers\StringGenerator;
use common\service\payment\PaymentApiInterface;
use common\service\payment\TransactionResponse;
use yii\base\BaseObject;

class PayboxApi extends BaseObject implements PaymentApiInterface
{
    const API_URL = 'https://api.paybox.money';

    protected string $merchantId;
    protected string $secretKey;

    public function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Функция превращает многомерный массив в плоский
     */
    private function makeFlatParamsArray(array $arrParams, string $parent_name = '')
    {
        $arrFlatParams = [];
        $i = 0;
        foreach ($arrParams as $key => $val) {
            $i++;
            $name = $parent_name . $key . sprintf('%03d', $i);
            if (is_array($val)) {
                $arrFlatParams = array_merge($arrFlatParams, $this->makeFlatParamsArray($val, $name));
                continue;
            }
            $arrFlatParams += [$name => (string) $val];
        }

        return $arrFlatParams;
    }

    /**
     * Выполнить запрос
     * @param string $urlAddon
     * @param array $params
     * @param array $headers
     * @return mixed
     * @throws PayboxApiException
     */
    private function execute(string $urlAddon, array $params = [], array $headers = [])
    {
        $curl = curl_init(self::API_URL . $urlAddon);
        $headers['Content-type'] = 'form-data';
        $params['pg_merchant_id'] = $this->merchantId;

        $params = $this->signParams($params, trim($urlAddon, "/ "));

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        try {
            $response = curl_exec($curl);
            $err = curl_error($curl);
        } catch (\Throwable $ex) {
            throw new PayboxApiException($ex->getMessage(), $ex->getCode(), $ex);
        } finally {
            if (false !== $curl) {
                curl_close($curl);
            }
        }

        if (!$response) {
            throw new PayboxApiException("Error: $err");
        }

        $data = simplexml_load_string($response);
        if ($data === false) {
            throw new PayboxApiException("Wrong response: $response");
        }

        $data = (array) $data;
        if (array_key_exists('pg_status', $data)
            && $data['pg_status'] !== 'ok') {
            throw new PayboxApiException($data['pg_error_code'] . ': ' . $data['pg_error_description']);
        }

        return $data;
    }

    /**
     * @param array<mixed> $details
     * @throws PayboxApiException
     */
    public function payCreate(float $amount, string $paymentId, ?string $returnUrl = null, array $details = []): TransactionResponse
    {
        $params = [
            'pg_amount' => (int) round($amount),
            'pg_order_id' => $paymentId,
            'pg_description' => $details['description'] ?? '',
            'pg_currency' => 'UZS',
            'pg_request_method' => 'POST',
            'pg_success_url' => $returnUrl,
            'pg_failure_url' => $returnUrl . '&success=0',
            'pg_testing_mode' => YII_ENV_DEV ? 1 : 0,
        ];
        if (isset($details['ip'])) {
            $params['pg_user_ip'] = $details['ip'];
        }
        $response = $this->execute('/init_payment.php', $params);

        if (array_key_exists('pg_payment_id', $response)) {
            return new TransactionResponse(
                (string) $response['pg_payment_id'],
                (string) $response['pg_redirect_url'],
                $response
            );
        }

        throw new PayboxApiException('Wrong response: ' . print_r($response, true));
    }

    public function signParams(array $params, string $scriptName): array
    {
        if (!isset($params['pg_salt'])) {
            $params['pg_salt'] = StringGenerator::generate(mt_rand(5, 15), true, true, true);
        }

// Превращаем объект запроса в плоский массив
        $requestForSignature = $this->makeFlatParamsArray($params);

// Генерация подписи
        ksort($requestForSignature); // Сортировка по ключю
        array_unshift($requestForSignature, $scriptName); // Добавление в начало имени скрипта
        $requestForSignature[] = $this->secretKey; // Добавление в конец секретного ключа

        $params['pg_sig'] = md5(implode(';', $requestForSignature)); // Полученная подпись

        return $params;
    }
}
