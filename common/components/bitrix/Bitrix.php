<?php

namespace common\components\bitrix;

use backend\models\WelcomeLesson;
use common\models\Group;
use common\models\User;
use Psr\Log\LoggerInterface;
use yii\base\BaseObject;

class Bitrix extends BaseObject
{
    const ORIGINATOR_ID = '5plus.uz';

    const SUBJECT_LIST = [
        64 => "general english",
        65 => "ielts",
        66 => "математика (на русском)",
        67 => "математика (на английском)",
        68 => "физика",
        69 => "химия",
        70 => "биология",
        71 => "история",
        72 => "русский язык и литература",
        73 => "рус тили (начальный)",
        74 => "sat",
        75 => "toefl",
        76 => "gmat, gre",
        77 => "немецкий язык",
        78 => "корейский язык",
        79 => "5+ kids",
        80 => "другое",
    ];

    const DEAL_STATUS_NEW = 'NEW';
    const DEAL_STATUS_WELCOME_LESSON = '1';
    const DEAL_STATUS_WELCOME_INVITE = '5';
    const DEAL_STATUS_STUDY = '2';
    const DEAL_STATUS_WAITING = '4';
    const DEAL_STATUS_CALLING = '3';
    const DEAL_STATUS_SUCCESSFUL = 'WON';
    const DEAL_STATUS_FAILED = 'LOSE';
    const DEAL_STATUS_ANALYZE = 'APOLOGY';

    const DEAL_OPEN_STATUSES = [
        self::DEAL_STATUS_NEW,
        self::DEAL_STATUS_WELCOME_INVITE,
        self::DEAL_STATUS_WELCOME_LESSON,
        self::DEAL_STATUS_WAITING,
        self::DEAL_STATUS_CALLING
    ];

    const USER_ROLE_MAPPER = [
        User::ROLE_PUPIL => 'SUPPLIER',
        User::ROLE_PARENTS => 'CLIENT',
        User::ROLE_COMPANY => 'CLIENT',
    ];

    const USER_GROUP_PARAM = 'UF_CRM_1565601293787';
    const USER_SUBJECT_PARAM = 'UF_CRM_1565334914';
    const USER_TEACHER_PARAM = 'UF_CRM_1565601761';
    const USER_WEEKDAYS_PARAM = 'UF_CRM_1565335047';
    const USER_WEEKTIME_PARAM = 'UF_CRM_1565601737';

    const DEAL_GROUP_PARAM = 'UF_CRM_1565613251';
    const DEAL_SUBJECT_PARAM = 'UF_CRM_1565870533';
    const DEAL_TEACHER_PARAM = 'UF_CRM_1565613273';
    const DEAL_WEEKDAYS_PARAM = 'UF_CRM_1565870276';
    const DEAL_WEEKTIME_PARAM = 'UF_CRM_1565613385';

    const SUBJECT_OTHER = 80;

    const WEEKDAY_LIST = [
        290,
        291,
        292,
        293,
        294,
        295,
        296
    ];

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

    public function getSubjectIdByGroup(Group $group): int
    {
        return $group->subject->bitrix_id ?: Bitrix::SUBJECT_OTHER;
    }

    public function getSubjectIdByWelcomeLesson(WelcomeLesson $lesson): int
    {
        return $lesson->subject->bitrix_id ?: Bitrix::SUBJECT_OTHER;
    }

    /**
     * @param string $method
     * @param array $params
     * @param bool $rawResponse
     * @return array|mixed
     */
    public function call(string $method, array $params = [], bool $rawResponse = false)
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
                $result = array_key_exists('result', $jsonData) && !$rawResponse ? $jsonData['result'] : $jsonData;
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['method' => $method, 'params' => $params]);
            if (is_resource($curl)) curl_close($curl);
        }

        return $result;
    }
}
