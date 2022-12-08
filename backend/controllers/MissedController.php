<?php

namespace backend\controllers;
use backend\models\Event;
use backend\models\EventMember;
use backend\models\UserCall;
use common\components\ComponentContainer;
use common\components\CourseComponent;
use common\models\Course;
use common\models\CourseConfig;
use common\models\GroupParam;
use common\models\CourseStudent;
use DateTimeImmutable;
use Exception;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * MissedController implements list of students that missing lessons.
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
                ->andWhere(['course_id' => $course->id, 'status' => Event::STATUS_PASSED])
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

        $courseStudentId = Yii::$app->request->post('courseStudent');
        if (!$courseStudentId) throw new BadRequestHttpException('Wrong request');
        $courseStudent = CourseStudent::findOne($courseStudentId);
        if (!$courseStudent) throw new BadRequestHttpException('Wrong request');
        $callResult = Yii::$app->request->post('callResult')[$courseStudentId];
        $comment = Yii::$app->request->post('callComment')[$courseStudentId];
        switch ($callResult) {
            case 'fail': $comment = 'Недозвон'; break;
            case 'phone': $comment = 'Неправильный номер телефона'; break;
        }

        $userCall = new UserCall();
        $userCall->user_id = $courseStudent->user_id;
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
     *
     * @param int $courseId
     * @param int $year
     * @param int $month
     *
     * @return mixed
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionTable(int $courseId = 0, int $year = 0, int $month = 0)
    {
        $this->checkAccess('viewMissed');

        $teacherId = null;
        if (Yii::$app->user->can('teacher')) {
            $teacherId = Yii::$app->user->identity->teacher_id;
        }

        if (!$year) $year = intval(date('Y'));
        if (!$month) $month = intval(date('n'));
        $dateStart = new DateTimeImmutable("$year-$month-01 midnight");
        $dateEnd = $dateStart->modify('+1 month -1 second');

        $eventMap = [];
        $dataMap = [];
        $course = null;
        if ($courseId) {
            $course = Course::findOne($courseId);
            if (!$course) throw new BadRequestHttpException('Group not found');

            $eventsQuery = Event::find()
                ->alias('e')
                ->andWhere(['e.course_id' => $course->id])
                ->andWhere(['between', 'e.event_date', $dateStart->format('Y-m-d H:i:s'), $dateEnd->format('Y-m-d H:i:s')])
                ->with('members.courseStudent.user');
            if ($teacherId) {
                $eventsQuery->innerJoin(
                    ['cc' => CourseConfig::tableName()],
                    'e.course_id = cc.course_id AND cc.date_from <= e.event_date AND (cc.date_to IS NULL OR cc.date_to > e.event_date)'
                )
                    ->andWhere(['cc.teacher_id' => $teacherId]);
            }

            /** @var Event $event */
            foreach ($eventsQuery->all() as $event) {
                $eventMap[(int)$event->eventDateTime->format('j')] = $event;
                foreach ($event->members as $eventMember) {
                    if (!array_key_exists($eventMember->course_student_id, $dataMap)) {
                        $dataMap[$eventMember->course_student_id] = ['student_name' => $eventMember->courseStudent->user->name];
                    }
                    $dataMap[$eventMember->course_student_id][(int)$event->eventDateTime->format('j')] = $eventMember;
                }
            }
            usort($dataMap, function($a, $b) { return $a['student_name'] <=> $b['student_name']; });
        }

        $courseQuery = Course::find()->andWhere(['active' => Course::STATUS_ACTIVE]);
        if ($teacherId) {
            $ids = CourseConfig::find()->andWhere(['teacher_id' => $teacherId])->select('course_id')->distinct()->column();
            $courseQuery->andWhere(['id' => $ids]);
        }
        
        return $this->render('table', [
            'date' => $dateStart,
            'daysCount' => intval($dateEnd->format('d')),
            'dataMap' => $dataMap,
            'eventMap' => $eventMap,
            'courses' => CourseComponent::sortCoursesByName($courseQuery->all()),
            'course' => $course,
        ]);
    }
}
