<?php

namespace backend\controllers;
use backend\models\Event;
use backend\models\EventMember;
use common\models\Group;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * MissedController implements list of pupils that missing lessons.
 */
class MissedController extends AdminController
{
    const MISS_LIMIT = 3;

    public function actionList()
    {
        if (!\Yii::$app->user->can('viewMissed')) throw new ForbiddenHttpException('Access denied!');

        /** @var Group[] $groups */
        $groups = Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->all();
        $resultMap = [];
        $groupMap = [];
        foreach ($groups as $group) {
            $resultMap[$group->id] = [];
            $groupMap[$group->id] = $group;
            /** @var Event[] $lastEvents */
            $lastEvents = Event::find()
                ->andWhere(['group_id' => $group->id, 'status' => Event::STATUS_PASSED])
                ->with('members.groupPupil')
                ->orderBy(['event_date' => SORT_DESC])
                ->limit(self::MISS_LIMIT)
                ->all();
            $missedMap = [];
            $groupPupilMap = [];
            foreach ($lastEvents as $event) {
                foreach ($event->members as $eventMember) {
                    if ($eventMember->status == EventMember::STATUS_MISS) {
                        $missedMap[$eventMember->group_pupil_id] = ($missedMap[$eventMember->group_pupil_id] ?? 0) + 1;
                        $groupPupilMap[$eventMember->group_pupil_id] = $eventMember->groupPupil;
                    }
                }
            }
            foreach ($missedMap as $groupPupilId => $missedCount) {
                if ($missedCount == self::MISS_LIMIT) {
                    $resultMap[$group->id][] = $groupPupilMap[$groupPupilId]->user;
                }
            }
        }

        return $this->render('list', ['resultMap' => $resultMap, 'groupMap' => $groupMap]);
    }

    /**
     * Monitor teachers' salary.
     * @param int $groupId
     * @param int $year
     * @param int $month
     * @return mixed
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionTable(int $groupId = 0, int $year = 0, int $month = 0)
    {
        if (!\Yii::$app->user->can('viewMissed')) throw new ForbiddenHttpException('Access denied!');

        if (!$year) $year = intval(date('Y'));
        if (!$month) $month = intval(date('n'));
        $dateStart = new \DateTime("$year-$month-01 00:00:00");
        $dateEnd = clone $dateStart;
        $dateEnd->modify('+1 month')->modify('-1 second');

        $dataMap = [];
        $group = null;
        if ($groupId) {
            $group = Group::findOne($groupId);
            if (!$group) throw new BadRequestHttpException('Group not found');

            /** @var Event[] $events */
            $events = Event::find()
                ->andWhere(['group_id' => $group->id])
                ->andWhere(['between', 'event_date', $dateStart->format('Y-m-d H:i:s'), $dateEnd->format('Y-m-d H:i:s')])
                ->with('members.groupPupil.user')
                ->all();
            foreach ($events as $event) {
                foreach ($event->members as $eventMember) {
                    if (!array_key_exists($eventMember->group_pupil_id, $dataMap)) {
                        $dataMap[$eventMember->group_pupil_id] = [0 => $eventMember->groupPupil->user->name];
                    }
                    $dataMap[$eventMember->group_pupil_id][intval($event->eventDateTime->format('j'))] = $eventMember->status;
                }
            }
            usort($dataMap, function($a, $b) { return $a[0] <=> $b[0]; });
        }

        return $this->render('table', [
            'date' => $dateStart,
            'daysCount' => intval($dateEnd->format('d')),
            'dataMap' => $dataMap,
            'groups' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->orderBy(['name' => SORT_ASC])->all(),
            'group' => $group,
        ]);
    }
}