<?php

namespace common\components\bitrix;

use common\components\ComponentContainer;
use common\models\GroupParam;
use common\models\Payment;

class PaymentSyncronizer
{
    /** @var Bitrix */
    private $client;

    public function __construct(Bitrix $client)
    {
        $this->client = $client;
    }

    private function markPaymentSynced(Payment $payment): void
    {
        $payment->bitrix_sync_status = Payment::STATUS_ACTIVE;
        if (!$payment->save()) {
            ComponentContainer::getErrorLogger()->logError("bitrix/sync", "id: $payment->id, errors: " . $payment->getErrorsAsString(), true);
        }
    }

    public function syncNextPayment()
    {
        /** @var Payment $toSync */
        $toSync = Payment::find()->andWhere(['>', 'amount', 0])->andWhere([
            'bitrix_sync_status' => Payment::STATUS_INACTIVE,
        ])->one();
        if (!$toSync) return false;

        if (!$toSync->user->bitrix_id) {
            ComponentContainer::getErrorLogger()->logError("bitrix/syncNextPayment", "User is not synced, id: {$toSync->user->id}", true);
            return false;
        }

        $groupParam = GroupParam::findByDate($toSync->group, $toSync->createDate);
        $schedule = $groupParam ? $groupParam->scheduleData : $toSync->group->scheduleData;

        $method = 'crm.deal.update';
        $params = ['fields' => []];
        $subjectId = $this->client->getSubjectIdByGroup($toSync->group, 'deal');

        $deals = ComponentContainer::getBitrix()->call(
            'crm.deal.list',
            ['filter' => [
                'CONTACT_ID' => $toSync->user->bitrix_id,
                'STAGE_ID' => Bitrix::DEAL_STATUS_STUDY,
                Bitrix::DEAL_SUBJECT_PARAM => $subjectId,
            ]]
        );
        if (!empty($deals)) {
            $params['id'] = reset($deals)['ID'];
        } else {
            $deals = ComponentContainer::getBitrix()->call(
                'crm.deal.list',
                ['filter' => [
                    'CONTACT_ID' => $toSync->user->bitrix_id,
                    'STAGE_ID' => Bitrix::DEAL_OPEN_STATUSES,
                    Bitrix::DEAL_SUBJECT_PARAM => $subjectId,
                ]]
            );
            if (!empty($deals)) {
                $params['id'] = reset($deals)['ID'];
            } else {
                $method = 'crm.deal.add';
                $params['fields']['OPPORTUNITY'] = $toSync->amount;
                $params['fields']['CURRENCY_ID'] = 'UZS';
                $params['fields']['CONTACT_IDS'] = [$toSync->user->bitrix_id];
                $params['fields']['TITLE'] = $toSync->group->name;
                $params['fields']['BEGINDATE'] = $params['fields']['CLOSEDATE'] = $toSync->createDate->format('c');
                $params['fields']['COMMENTS'] = $toSync->comment;
                $params['fields']['ORIGIN_ID'] = $toSync->id;
            }
            $params['fields']['TYPE_ID'] = 'SALE';
            $params['fields']['STAGE_ID'] = Bitrix::DEAL_STATUS_STUDY;
            $params['fields']['ORIGINATOR_ID'] = Bitrix::ORIGINATOR_ID;
        }

        $weekdays = $weektimes = [];
        foreach ($schedule as $key => $value) {
            if ($value) {
                $weekdays[] = Bitrix::WEEKDAY_LIST[$key];
                $weektimes[] = $value;
            }
        }
        $groups = [$toSync->group->name];
        $subjects = [$this->client->getSubjectIdByGroup($toSync->group, 'deal')];
        $teachers = [$groupParam ? $groupParam->teacher->name : $toSync->group->teacher->name];

        $params['fields'][Bitrix::DEAL_GROUP_PARAM] = array_unique($groups);
        $params['fields'][Bitrix::DEAL_SUBJECT_PARAM] = array_unique($subjects);
        $params['fields'][Bitrix::DEAL_TEACHER_PARAM] = array_unique($teachers);
        $params['fields'][Bitrix::DEAL_WEEKDAYS_PARAM] = array_unique($weekdays);
        $params['fields'][Bitrix::DEAL_WEEKTIME_PARAM] = array_unique($weektimes);

        $res3 = ComponentContainer::getBitrix()->call($method, $params);
        if (!$res3) {
            ComponentContainer::getErrorLogger()->logError("bitrix/$method", "result: $res3, id: $toSync->id", true);
            return true;
        }

        $this->markPaymentSynced($toSync);
        return true;
    }
}
