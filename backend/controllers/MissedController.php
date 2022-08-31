<?php

namespace backend\controllers;
use backend\models\Event;
use backend\models\EventMember;
use backend\models\UserCall;
use common\components\ComponentContainer;
use common\models\Course;
use common\models\GroupParam;
use common\models\CourseStudent;
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
        $this->checkAccess('callMissed');

        /** @var Course[] $courses */
        $courses = Course::find()->andWhere(['active' => Course::STATUS_ACTIVE])->orderBy(['name' => SORT_ASC])->all();
        $courseMap = [];
        foreach ($courses as $course) {
            $courseMap[$course->id] = [
                'entity' => $course,
                'students' => [],
            ];
            /** @var Event[] $lastEvents */
            $lastEvents = Event::find()
                ->andWhere(['group_id' => $course->id, 'status' => Event::STATUS_PASSED])
                ->with('members.courseStudent')
                ->orderBy(['event_date' => SORT_DESC])
                ->limit(self::MISS_LIMIT)
                ->all();
            $missedMap = [];
            /** @var CourseStudent[] $courseStudentMap */
            $courseStudentMap = [];
            foreach ($lastEvents as $event) {
                foreach ($event->members as $eventMember) {
                    if ($eventMember->status == EventMember::STATUS_MISS) {
                        $missedMap[$eventMember->course_student_id] = ($missedMap[$eventMember->course_student_id] ?? 0) + 1;
                        $courseStudentMap[$eventMember->course_student_id] = $eventMember->courseStudent;
                    }
                }
            }
            foreach ($missedMap as $courseStudentId => $missedCount) {
                if ($missedCount == self::MISS_LIMIT) {
                    $studentData = [
                        'courseStudent' => $courseStudentMap[$courseStudentId],
                    ];
                    /** @var Event $lastVisit */
                    $lastVisit = Event::find()->alias('e')
                        ->joinWith(['members em'], false)
                        ->andWhere([
                            'e.course_id' => $courseStudentMap[$courseStudentId]->course_id,
                            'e.status' => Event::STATUS_PASSED,
                            'em.course_student_id' => $courseStudentId,
                            'em.status' => EventMember::STATUS_ATTEND,
                        ])
                        ->orderBy(['e.event_date' => SORT_DESC])
                        ->one();
                    $callDateFrom = $lastVisit
                        ? $lastVisit->eventDateTime
                        : $courseStudentMap[$courseStudentId]->startDateObject;
                    $studentData['calls'] = UserCall::find()
                        ->andWhere(['user_id' => $courseStudentMap[$courseStudentId]->user_id])
                        ->andWhere(['>', 'created_at', $callDateFrom->format('Y-m-d H:i:s')])
                        ->orderBy(['created_at' => SORT_ASC])
                        ->all();
                    $courseMap[$course->id]['students'][] = $studentData;
                }
            }
        }

        return $this->render('list', ['courseMap' => $courseMap]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionCall()
    {
        if (!Yii::$app->request->isPost) throw new BadRequestHttpException('Only POST requests allowed');
        $this->checkAccess('callMissed');

        $groupPupilId = Yii::$app->request->post('groupPupil');
        if (!$groupPupilId) throw new BadRequestHttpException('Wrong request');
        $groupPupil = CourseStudent::findOne($groupPupilId);
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
        $this->checkAccess('viewMissed');

        $teacherId = null;
        if (Yii::$app->user->can('teacher')) {
            $teacherId = Yii::$app->user->identity->teacher_id;
        }

        if (!$year) $year = intval(date('Y'));
        if (!$month) $month = intval(date('n'));
        $dateStart = new \DateTimeImmutable("$year-$month-01 midnight");
        $dateEnd = $dateStart->modify('+1 month -1 second');

        $eventMap = [];
        $dataMap = [];
        $group = null;
        if ($groupId) {
            $group = Course::findOne($groupId);
            if (!$group) throw new BadRequestHttpException('Group not found');
            if ($teacherId) {
                $groupParam = GroupParam::findByDate($group, $dateStart);
                if (($groupParam && $groupParam->teacher_id != $teacherId) || $group->teacher_id != $teacherId) {
                    throw new ForbiddenHttpException('Access denied!');
                }
            }

            /** @var Event[] $events */
            $events = Event::find()
                ->andWhere(['group_id' => $group->id])
                ->andWhere(['between', 'event_date', $dateStart->format('Y-m-d H:i:s'), $dateEnd->format('Y-m-d H:i:s')])
                ->with('members.groupPupil.user')
                ->all();
            foreach ($events as $event) {
                $eventMap[(int)$event->eventDateTime->format('j')] = $event;
                foreach ($event->members as $eventMember) {
                    if (!array_key_exists($eventMember->course_student_id, $dataMap)) {
                        $dataMap[$eventMember->course_student_id] = [0 => $eventMember->groupPupil->user->name];
                    }
                    $dataMap[$eventMember->course_student_id][(int)$event->eventDateTime->format('j')] = $eventMember->toArray();
                }
            }
            usort($dataMap, function($a, $b) { return $a[0] <=> $b[0]; });
        }

        $groupQuery = Course::find()->andWhere(['active' => Course::STATUS_ACTIVE])->orderBy(['name' => SORT_ASC]);
        if ($teacherId) {
            $ids = GroupParam::find()->andWhere(['teacher_id' => $teacherId])->select('group_id')->distinct(true)->column();
            $groupQuery->andWhere(['id' => $ids]);
        }
        
        return $this->render('table', [
            'date' => $dateStart,
            'daysCount' => intval($dateEnd->format('d')),
            'dataMap' => $dataMap,
            'eventMap' => $eventMap,
            'groups' => $groupQuery->all(),
            'group' => $group,
        ]);
    }
}
