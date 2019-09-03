<?php

namespace common\components\bitrix;

use common\components\ComponentContainer;
use common\models\GroupParam;
use common\models\Payment;
use common\models\User;

class PaymentSyncronizer
{
    /** @var Bitrix */
    private $client;

    public function __construct(Bitrix $client)
    {
        $this->client = $client;
    }

    private function findExistingOpenDeal(User $user): ?array
    {
        $offset = 0;
        do {
            $response = ComponentContainer::getBitrix()->call(
                'crm.deal.list',
                ['filter' => ['CONTACT_ID' => $user->bitrix_id], 'start' => $offset],
                true
            );
            foreach ($response['result'] as $deal) {
                if (in_array($deal['STAGE_ID'], Bitrix::DEAL_OPEN_STATUSES)) {
                    return $deal;
                }
            }

            $offset = $response['next'] ?? 0;
        } while (isset($response['next']));

        return null;
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
        }

        $groupParam = GroupParam::findByDate($toSync->group, $toSync->createDate);
        $schedule = $groupParam ? $groupParam->scheduleData : $toSync->group->scheduleData;
        $weekdays = [];
        $weektimes = [];
        foreach ($schedule as $key => $value) {
            if ($value) {
                $weekdays[] = Bitrix::WEEKDAY_LIST[$key];
                $weektimes[] = $value;
            }
        }
        $params = [
            'fields' => [
                'OPPORTUNITY' => $toSync->amount,
                Bitrix::DEAL_GROUP_PARAM => [$toSync->group->name],
                Bitrix::DEAL_SUBJECT_PARAM => [$this->client->getSubjectIdByGroup($toSync->group, 'deal')],
                Bitrix::DEAL_TEACHER_PARAM => [$groupParam ? $groupParam->teacher->name : $toSync->group->teacher->name],
                Bitrix::DEAL_WEEKDAYS_PARAM => $weekdays,
                Bitrix::DEAL_WEEKTIME_PARAM => array_unique($weektimes),
            ]
        ];

        $response = ComponentContainer::getBitrix()->call(
            'crm.deal.list',
            ['filter' => [
                'CONTACT_ID' => $toSync->user->bitrix_id,
                'ORIGINATOR_ID' => Bitrix::ORIGINATOR_ID,
                'ORIGIN_ID' => $toSync->id]]);
        $method = 'crm.deal.update';
        if (!empty($response)) {
            $deal = reset($response);
            $params['id'] = $deal['ID'];
        } else {
            $existingDeal = $this->findExistingOpenDeal($toSync->user);
            if ($existingDeal) {
                $params['id'] = $existingDeal['ID'];
            } else {
                $method = 'crm.deal.add';
            }
            $params['fields']['CONTACT_IDS'] = [$toSync->user->bitrix_id];
            $params['fields']['TITLE'] = $toSync->group->name;
            $params['fields']['CURRENCY_ID'] = 'UZS';
            $params['fields']['TYPE_ID'] = 'SALE';
            $params['fields']['STAGE_ID'] = Bitrix::DEAL_STATUS_STUDY;
            $params['fields']['BEGINDATE'] = $params['fields']['CLOSEDATE'] = $toSync->createDate->format('c');
            $params['fields']['COMMENTS'] = $toSync->comment;
            $params['fields']['ORIGINATOR_ID'] = Bitrix::ORIGINATOR_ID;
            $params['fields']['ORIGIN_ID'] = $toSync->id;
        }

        $res3 = ComponentContainer::getBitrix()->call($method, $params);
        if (!$res3) {
            ComponentContainer::getErrorLogger()->logError("bitrix/$method", "result: $res3, id: $toSync->id", true);
        } else {
            $toSync->bitrix_sync_status = Payment::STATUS_ACTIVE;
            if (!$toSync->save()) {
                ComponentContainer::getErrorLogger()->logError("bitrix/$method", "id: $toSync->id, errors: " . $toSync->getErrorsAsString(), true);
            }
        }

        return false;
    }
}
