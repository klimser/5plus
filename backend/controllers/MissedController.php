<?php

namespace backend\controllers;
use backend\models\Event;
use backend\models\EventMember;
use backend\models\UserCall;
use common\components\ComponentContainer;
use common\models\Group;
use common\models\GroupParam;
use common\models\GroupPupil;
use Exception;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * MissedController implements list of pupils that missing lessons.
 */
class MissedController extends AdminController
{
    const MISS_LIMIT = 2;

    public function actionList()
    {
        if (!Yii::$app->user->can('callMissed')) throw new ForbiddenHttpException('Access denied!');

        /** @var Group[] $groups */
        $groups = Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->orderBy(['name' => SORT_ASC])->all();
        $groupMap = [];
        foreach ($groups as $group) {
            $groupMap[$group->id] = [
                'entity' => $group,
                'pupils' => [],
            ];
            /** @var Event[] $lastEvents */
            $lastEvents = Event::find()
                ->andWhere(['group_id' => $group->id, 'status' => Event::STATUS_PASSED])
                ->with('members.groupPupil')
                ->orderBy(['event_date' => SORT_DESC])
                ->limit(self::MISS_LIMIT)
                ->all();
            $missedMap = [];
            /** @var GroupPupil[] $groupPupilMap */
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
                    $pupilData = [
                        'groupPupil' => $groupPupilMap[$groupPupilId],
                        'calls' => [],
                    ];
                    /** @var Event $lastVisit */
                    $lastVisit = Event::find()->alias('e')
                        ->joinWith(['members em'], false)
                        ->andWhere([
                            'e.group_id' => $groupPupilMap[$groupPupilId]->group_id,
                            'e.status' => Event::STATUS_PASSED,
                            'em.group_pupil_id' => $groupPupilId,
                            'em.status' => EventMember::STATUS_ATTEND,
                        ])
                        ->orderBy(['e.event_date' => SORT_DESC])
                        ->one();
                    $callDateFrom = $lastVisit
                        ? $lastVisit->eventDateTime
                        : $groupPupilMap[$groupPupilId]->startDateObject;
                    $pupilData['calls'] = UserCall::find()
                        ->andWhere(['user_id' => $groupPupilMap[$groupPupilId]->user_id])
                        ->andWhere(['>', 'created_at', $callDateFrom->format('Y-m-d H:i:s')])
                        ->orderBy(['created_at' => SORT_ASC])
                        ->all();
                    $groupMap[$group->id]['pupils'][] = $pupilData;
                }
            }
        }

        return $this->render('list', ['groupMap' => $groupMap]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionCall()
    {
        if (!Yii::$app->request->isPost) throw new BadRequestHttpException('Only POST requests allowed');
        if (!Yii::$app->user->can('callMissed')) throw new ForbiddenHttpException('Access denied!');

        $groupPupilId = Yii::$app->request->post('groupPupil');
        if (!$groupPupilId) throw new BadRequestHttpException('Wrong request');
        $groupPupil = GroupPupil::findOne($groupPupilId);
        if (!$groupPupil) throw new BadRequestHttpException('Wrong request');
        $callResult = Yii::$app->request->post('callResult')[$groupPupilId];
        $comment = Yii::$app->request->post('callComment')[$groupPupilId];
        switch ($callResult) {
            case 'fail': $comment = 'Недозвон'; break;
            case 'phone': $comment = 'Неправильный номер телефона'; break;
        }

        $userCall = new UserCall();
        $userCall->user_id = $groupPupil->user_id;
        $userCall->admin_id = Yii::$app->user->id;
        $userCall->comment = $comment;
        if (!$userCall->save()) {
            ComponentContainer::getErrorLogger()->logError('missed/call', $userCall->getErrorsAsString(), true);
            throw new Exception('Fatal error');
        }

        return $this->redirect(['list']);
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
        if (!Yii::$app->user->can('viewMissed')) throw new ForbiddenHttpException('Access denied!');

        $teacherId = null;
        if (Yii::$app->user->can('teacher')) {
            $teacherId = Yii::$app->user->identity->teacher_id;
        }

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
            if ($teacherId) {
                $groupParam = GroupParam::findByDate($group, $date);
            }

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
