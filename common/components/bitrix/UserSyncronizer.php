<?php

namespace common\components\bitrix;

use common\components\ComponentContainer;
use common\models\GroupParam;
use common\models\User;

class UserSyncronizer
{
    /** @var Bitrix */
    private $client;

    public function __construct(Bitrix $client)
    {
        $this->client = $client;
    }

    public function trimUsers(): void
    {
        $offset = 0;
        do {
            $response = $this->client->call('crm.contact.list', ['select' => ['NAME', 'LAST_NAME'], 'start' => $offset], true);
            $userList = $response['result'];
            foreach ($userList as $user) {
                [$trimName, $trimSurname] = [trim($user['NAME']), trim($user['LAST_NAME'])];
                if ($trimName !== $user['NAME'] || $trimSurname !== $user['LAST_NAME']) {
                    $result = $this->client->call('crm.contact.update', ['id' => $user['ID'], 'fields' => ['NAME' => $trimName, 'LAST_NAME' => $trimSurname]]);
                    if (!$result) {
                        ComponentContainer::getErrorLogger()->logError('bitrix/trimUsers', "id: $user[ID]", true);
                    }
                }
            }
            $offset = $response['next'] ?? 0;
        } while (isset($response['next']));
    }

    private function processUserSearchResult($searchResults, User $pupil): bool
    {
        if (!empty($searchResults)) {
            foreach ($searchResults as $bitrixUser) {
                if (mb_strpos(mb_strtolower($pupil->name, 'UTF-8'), mb_strtolower($bitrixUser['NAME'], 'UTF-8'), 0, 'UTF-8')
                    || mb_strpos(mb_strtolower($pupil->name, 'UTF-8'), mb_strtolower($bitrixUser['LAST_NAME'], 'UTF-8'), 0, 'UTF-8')) {
                    $pupil->bitrix_id = $bitrixUser['ID'];
                    if (!$pupil->save()) {
                        ComponentContainer::getErrorLogger()
                            ->logError('bitrix/syncUsers', "id: $pupil->id, bitrixId: $bitrixUser[ID], errors: " . $pupil->getErrorsAsString(), true);
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function syncNextUser()
    {
        $toSync = User::findOne([
            'role' => array_keys(Bitrix::USER_ROLE_MAPPER),
            'bitrix_sync_status' => 0,
        ]);
        if (!$toSync) return false;

        $groups = $teachers = $weekdays = $weektimes = [];
        $subjects = [];
        $phoneMap = [];
        if (!$toSync->bitrix_id) {
            $res = $this->processUserSearchResult(ComponentContainer::getBitrix()->call(
                'crm.contact.list',
                [ 'filter' => [ 'PHONE' => $toSync->phoneInternational, 'TYPE_ID' => Bitrix::USER_ROLE_MAPPER[$toSync->role] ] ]
            ),
                $toSync);
            if ($res !== true && $toSync->phone2) {
                $this->processUserSearchResult(ComponentContainer::getBitrix()->call(
                    'crm.contact.list',
                    [ 'filter' => [ 'PHONE' => $toSync->phone2International, 'TYPE_ID' => Bitrix::USER_ROLE_MAPPER[$toSync->role] ] ]
                ),
                    $toSync);
            }
        } else {
            $bitrixUser = $this->client->call('crm.contact.get', [ 'id' => $toSync->bitrix_id ]);
            foreach ($bitrixUser['PHONE'] as $phone) {
                $phoneMap[$phone['VALUE_TYPE']] = $phone['ID'];
            }
        }

        $nameParts = $toSync->nameParts;
        $mobilePhone = ['VALUE' => $toSync->phoneInternational, 'VALUE_TYPE' => 'MOBILE'];
        if (array_key_exists('MOBILE', $phoneMap)) {
            $mobilePhone['ID'] = $phoneMap['MOBILE'];
        }
        $phones = [$mobilePhone];
        if ($toSync->phone2 || array_key_exists('HOME', $phoneMap)) {
            $homePhone = ['VALUE' => $toSync->phone2International, 'VALUE_TYPE' => 'HOME'];
            if (array_key_exists('HOME', $phoneMap)) {
                $homePhone['ID'] = $phoneMap['HOME'];
            }
            $phones[] = $homePhone;
        }

        foreach ($toSync->groupPupils as $groupPupil) {
            $groups[] = $groupPupil->group->name;
            $groupParam = GroupParam::findByDate($groupPupil->group, $groupPupil->startDateObject);
            $schedule = $groupParam ? $groupParam->scheduleData : $groupPupil->group->scheduleData;
            foreach ($schedule as $key => $value) {
                if ($value) {
                    $weekdays[] = Bitrix::WEEKDAY_LIST[$key];
                    $weektimes[] = $value;
                }
            }
            $teachers[] = $groupParam ? $groupParam->teacher->name : $groupPupil->group->teacher->name;
            $subjects[] = $this->client->getSubjectIdByGroup($groupPupil->group, 'user');
        }

        $params = [
            'fields' => [
                'NAME' => $nameParts[1] ?? '',
                'SECOND_NAME' => $nameParts[2] ?? '',
                'LAST_NAME' => $nameParts[0],
                'TYPE_ID' => Bitrix::USER_ROLE_MAPPER[$toSync->role],
                'PHONE' => $phones,
                'SOURCE_ID' => 'WEB',
                Bitrix::USER_SUBJECT_PARAM => array_unique($subjects),
                Bitrix::USER_GROUP_PARAM => array_unique($groups),
                Bitrix::USER_TEACHER_PARAM => array_unique($teachers),
                Bitrix::USER_WEEKDAYS_PARAM => array_unique($weekdays),
                Bitrix::USER_WEEKTIME_PARAM => array_unique($weektimes),
            ],
        ];
        if ($toSync->bitrix_id) {
            $params['id'] = $toSync->bitrix_id;
            $method = 'crm.contact.update';
        } else {
            $method = 'crm.contact.add';
        }
        $res3 = $this->client->call($method, $params);
        if (!$res3) {
            ComponentContainer::getErrorLogger()->logError("bitrix/$method", "result: $res3, id: $toSync->id", true);
        } else {
            $toSync->bitrix_sync_status = 1;
            if (!$toSync->bitrix_id) {
                $toSync->bitrix_id = intval($res3);
            }
            if (!$toSync->save()) {
                ComponentContainer::getErrorLogger()->logError("bitrix/$method", "id: $toSync->id, errors: " . $toSync->getErrorsAsString(), true);
            }
        }

        return false;
    }
}
