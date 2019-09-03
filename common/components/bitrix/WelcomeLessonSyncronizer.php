<?php

namespace common\components\bitrix;

use backend\models\WelcomeLesson;
use common\components\ComponentContainer;
use common\models\Payment;

class WelcomeLessonSyncronizer
{
    /** @var Bitrix */
    private $client;

    public function __construct(Bitrix $client)
    {
        $this->client = $client;
    }

    public function syncNextWelcomeLesson()
    {
        /** @var WelcomeLesson $toSync */
        $toSync = WelcomeLesson::find()->andWhere([
            'bitrix_sync_status' => Payment::STATUS_INACTIVE,
        ])->one();
        if (!$toSync) return false;

        if (!$toSync->user->bitrix_id) {
            ComponentContainer::getErrorLogger()->logError("bitrix/syncNextWelcomeLesson", "User is not synced, id: {$toSync->user->id}", true);
        }

        $params = [
            'fields' => [
                Bitrix::DEAL_SUBJECT_PARAM => [$this->client->getSubjectIdByWelcomeLesson($toSync, 'deal')],
                Bitrix::DEAL_TEACHER_PARAM => [$toSync->teacher->name],
                Bitrix::DEAL_WEEKDAYS_PARAM => [Bitrix::WEEKDAY_LIST[$toSync->lessonDateTime->format('N') - 1]],
            ]
        ];

        $response = ComponentContainer::getBitrix()->call(
            'crm.deal.list',
            ['filter' => [
                'CONTACT_ID' => $toSync->user->bitrix_id,
                'STAGE_ID' => Bitrix::DEAL_STATUS_WELCOME_INVITE,
                'ORIGINATOR_ID' => Bitrix::ORIGINATOR_ID,
                'ORIGIN_ID' => $toSync->id]]);
        if (!empty($response)) {
            $deal = reset($response);
            $method = 'crm.deal.update';
            $params['id'] = $deal['ID'];
        } else {
            $method = 'crm.deal.add';
            $params['fields']['CONTACT_IDS'] = [$toSync->user->bitrix_id];
            $params['fields']['TITLE'] = $toSync->subject->name;
            $params['fields']['CURRENCY_ID'] = 'UZS';
            $params['fields']['TYPE_ID'] = 'SALE';
            $params['fields']['STAGE_ID'] = $toSync->status == WelcomeLesson::STATUS_UNKNOWN ? Bitrix::DEAL_STATUS_WELCOME_INVITE : Bitrix::DEAL_STATUS_WELCOME_LESSON;
            $params['fields']['BEGINDATE'] = $toSync->lessonDateTime->format('c');
            $params['fields']['ORIGINATOR_ID'] = Bitrix::ORIGINATOR_ID;
            $params['fields']['ORIGIN_ID'] = $toSync->id;
        }

        $res3 = ComponentContainer::getBitrix()->call($method, $params);
        if (!$res3) {
            ComponentContainer::getErrorLogger()->logError("bitrix/$method", "result: $res3, id: $toSync->id", true);
        } else {
            $toSync->bitrix_sync_status = WelcomeLesson::STATUS_ACTIVE;
            if (!$toSync->save()) {
                ComponentContainer::getErrorLogger()->logError("bitrix/$method", "id: $toSync->id, errors: " . $toSync->getErrorsAsString(), true);
            }
        }

        return false;
    }
}
