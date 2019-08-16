<?php

namespace console\controllers;

use common\components\ComponentContainer;
use common\models\Payment;
use common\models\User;
use yii;
use yii\console\Controller;

/**
 * BitrixSyncController is used to sync data with Bitrix.
 */
class BitrixSyncController extends Controller
{
    const TIME_LIMIT = 50;

    const USER_ROLE_MAPPER = [
        User::ROLE_PUPIL => 'SUPPLIER',
        User::ROLE_PARENTS => 'CLIENT',
        User::ROLE_COMPANY => 'CLIENT',
    ];

    const SUBJECT_LIST = [
        "General ENGLISH" => 64,
        "IELTS" => 65,
        "Математика (на русском)" => 66,
        "Математика (на английском)" => 67,
        "Физика" => 68,
        "Химия" => 69,
        "Биология" => 70,
        "История" => 71,
        "Русский язык и литература" => 72,
        "Рус Тили (начальный)" => 73,
        "SAT" => 74,
        "TOEFL" => 75,
        "GMAT, GRE" => 76,
        "Немецкий язык" => 77,
        "Корейский язык" => 78,
        "5+ KIDS" => 79,
        "Другое" => 80,
    ];

    /** @var float */
    private $startTime;

    /**
     * @return bool
     */
    private function isTimeExceeded(): bool
    {
        return microtime(true) - $this->startTime > self::TIME_LIMIT;
    }

    /**
     * Search for a not sent notifications and sends it.
     * @return int
     */
    public function actionSend()
    {
        $this->startTime = microtime(true);

        // USERS

        $this->trimUsers();

        while ($this->syncNextUser()) {
            if ($this->isTimeExceeded()) return yii\console\ExitCode::OK;
        }

        // PAYMENTS

        while ($this->syncNextPayment()) {
            if ($this->isTimeExceeded()) return yii\console\ExitCode::OK;
        }

        return yii\console\ExitCode::OK;
    }

    private function trimUsers(): void
    {
        $userList = ComponentContainer::getBitrix()->call('crm.contact.list', ['select' => ['NAME', 'LAST_NAME']]);
        foreach ($userList as $user) {
            [$trimName, $trimSurname] = [trim($user['NAME']), trim($user['LAST_NAME'])];
            if ($trimName !== $user['NAME'] || $trimSurname !== $user['LAST_NAME']) {
                $result = ComponentContainer::getBitrix()->call('crm.contact.update', ['id' => $user['ID'], 'fields' => ['NAME' => $trimName, 'LAST_NAME' => $trimSurname]]);
                if (!$result) {
                    ComponentContainer::getErrorLogger()->logError('bitrix/trimUsers', "id: $user[ID]", true);
                }
            }
        }
    }

    private function syncNextUser()
    {
        $toSync = User::findOne([
            ['in', 'role', array_keys(self::USER_ROLE_MAPPER)],
            'bitrix_id' => null
        ]);
        if (!$toSync) return false;

        $processSearchResults = function ($searchResults) use ($toSync): bool {
            if (!empty($searchResults)) {
                foreach ($searchResults as $bitrixUser) {
                    if (mb_strpos($toSync->name, $bitrixUser['NAME'], 0, 'UTF-8')
                        && mb_strpos($toSync->name, $bitrixUser['LAST_NAME'], 0, 'UTF-8')) {
                        $toSync->bitrix_id = $bitrixUser['ID'];
                        if (!$toSync->save()) {
                            ComponentContainer::getErrorLogger()
                                ->logError('bitrix/syncUsers', "id: $toSync->id, bitrixId: $bitrixUser[ID], errors: " . $toSync->getErrorsAsString(), true);
                        }
                        return true;
                    }
                }
            }
            return false;
        };

        $res1 = $processSearchResults(ComponentContainer::getBitrix()->call(
            'crm.contact.list',
            ['filter' => ['PHONE' => $toSync->phoneInternational, 'TYPE_ID' => self::USER_ROLE_MAPPER[$toSync->role]]]
        ));
        if ($res1 === true) return true;

        if ($toSync->phone2) {
            $res2 = $processSearchResults(ComponentContainer::getBitrix()->call(
                'crm.contact.list',
                ['filter' => ['PHONE' => $toSync->phone2International, 'TYPE_ID' => self::USER_ROLE_MAPPER[$toSync->role]]]
            ));
            if ($res2 === true) return true;
        }

        $nameParts = $toSync->nameParts;
        $phones = [['VALUE' => $toSync->phoneInternational, 'VALUE_TYPE' => 'MAIN']];
        if ($toSync->phone2) {
            $phones[] = ['VALUE' => $toSync->phone2International, 'VALUE_TYPE' => 'MOBILE'];
        }
        $groups = [];
        foreach ($toSync->groups as $group) {
            if (array_key_exists($group->name, self::SUBJECT_LIST)) {
                $groups[] = self::SUBJECT_LIST[$group->name];
            }
        }
        $res3 = ComponentContainer::getBitrix()->call('crm.contact.add', [
            'fields' => [
                'NAME' => $nameParts[0],
                'SECOND_NAME' => $nameParts[2] ?? '',
                'LAST_NAME' => $nameParts[1] ?? '',
                'TYPE_ID' => self::USER_ROLE_MAPPER[$toSync->role],
                'PHONE' => $phones,
                'SOURCE_ID' => 'WEB',
                'UF_CRM_1565334914' => $groups,
            ],
        ]);
        if (!$res3) {
            ComponentContainer::getErrorLogger()->logError('bitrix/addUser', "result: $res3, id: $toSync->id", true);
        } else {
            $toSync->bitrix_id = intval($res3);
            if (!$toSync->save()) {
                ComponentContainer::getErrorLogger()->logError('bitrix/addUser', "id: $toSync->id, errors: " . $toSync->getErrorsAsString(), true);
            }
        }

        return true;
    }

    private function syncNextPayment()
    {
        return false;

        $toSync = Payment::findOne([
            ['>', 'amount', 0],
            'bitrix_id' => null
        ]);
        if (!$toSync) return false;
    }
}
