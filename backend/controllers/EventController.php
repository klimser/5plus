<?php

namespace backend\controllers;

use backend\components\EventComponent;
use backend\models\WelcomeLesson;
use common\components\Action;
use common\components\ComponentContainer;
use common\components\CourseComponent;
use common\components\helpers\WordForm;
use common\components\MoneyComponent;
use backend\models\Event;
use backend\models\EventMember;
use common\models\CourseConfig;
use common\models\GroupParam;
use DateTime;
use Exception;
use Throwable;
use yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EventController extends AdminController
{
    private string $adminRule = 'adminSchedule';
    protected $accessRule = 'manageSchedule';
    

    private function getLimitDate(): DateTime
    {
//        return new \DateTime('+30 minute');
        return new DateTime('tomorrow midnight');
    }

    /**
     * @param Event $event
     * @return bool
     * @throws Exception
     */
    private function isTeacherHasAccess(Event $event): bool
    {
        if (Yii::$app->user->can('teacher')) {
            $courseConfig = CourseComponent::getCourseConfig($event->course, new DateTime());
            return $courseConfig->teacher_id == Yii::$app->user->identity->teacher_id;
        }
        return true;
    }

    /**
     * Lists all Event models.
     * @param string|null $date
     * @return mixed
     * @throws Exception
     */
    public function actionIndex(?string $date = null)
    {
        $startDate = new \DateTimeImmutable(($date ?? 'now') . ' midnight');
        $endDate = $startDate->modify('+1 day');

        $eventsQuery = Event::find()
            ->alias('e')
            ->where('event_date > :startDate', [':startDate' => $startDate->format('Y-m-d H:i:s')])
            ->andWhere('event_date < :endDate', [':endDate' => $endDate->format('Y-m-d H:i:s')])
            ->with(['course', 'members.courseStudent.user', 'welcomeMembers'])
            ->orderBy(['event_date' => SORT_ASC]);
        
        if (Yii::$app->user->can('teacher')) {
            $teacherId = Yii::$app->user->identity->teacher_id;
            $eventsQuery->innerJoin(
                    ['cc' => CourseConfig::tableName()],
                    'e.course_id = cc.course_id AND cc.date_from <= e.event_date AND (cc.date_to IS NULL OR cc.date_to > e.event_date)'
                )
                ->andWhere(['cc.teacher_id' => $teacherId]);
        }
        
        $events = $eventsQuery->all();

        return $this->render('index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'events' => $events,
            'limitDate' => $this->getLimitDate(),
            'isTeacher' => Yii::$app->user->can('teacher'),
            'isAdmin' => Yii::$app->user->can($this->adminRule),
        ]);
    }

    public function actionGet(int $id)
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var Event $event */
        $event = Event::find()
            ->andWhere(['id' => $id])
            ->with(['members.courseStudent.user', 'welcomeMembers.user'])
            ->one();
        if (empty($event)) {
            return self::getJsonErrorResult('Event not found');
        }
        
        return self::getJsonOkResult(['eventData' => $this->prepareEventData($event)]);
    }

    public function actionChangeStatus(int $id)
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var Event $event */
        $event = Event::find()->andWhere(['id' => $id])->with('members.courseStudent.user')->one();
        if (!$event) {
            return self::getJsonErrorResult('Event not found');
        }

        $status = Yii::$app->getRequest()->post('status');
        
        if (!$this->isTeacherHasAccess($event)
            || (!Yii::$app->user->can($this->adminRule) && $event->status != Event::STATUS_UNKNOWN)
            || $event->eventDateTime > $this->getLimitDate()) {
            return self::getJsonErrorResult('Event edit denied!');
        } 
        if (!in_array($status, [Event::STATUS_PASSED, Event::STATUS_CANCELED])) {
            return self::getJsonErrorResult('Wrong status!');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $revertMemberStatuses = Event::STATUS_CANCELED == $event->status && Event::STATUS_PASSED == $status;
            $recalculateCharges = Event::STATUS_PASSED == $event->status && Event::STATUS_CANCELED == $status;
            if (!empty($event->status)) {
                ComponentContainer::getActionLogger()->log(Action::TYPE_EVENT_STATUS_REVERTED, null, null, $event->course);
            }
            $event->status = $status;
            if (!$event->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('event/change-status', $event->getErrorsAsString(), true);
                throw new Exception('Server error');
            }
            switch ($status) {
                case Event::STATUS_PASSED:
                    MoneyComponent::chargeByEvent($event);

                    foreach ($event->members as $member) {
                        MoneyComponent::setUserChargeDates($member->courseStudent->user, $event->course);
                        if ($member->courseStudent->user->getDebt($member->courseStudent->course)) {
                            ComponentContainer::getBotPush()->lowBalance($member->courseStudent);
                        }
                        if ($revertMemberStatuses) {
                            $member->status = EventMember::STATUS_UNKNOWN;
                            $member->save();
                        }
                    }
                    if ($revertMemberStatuses) {
                        foreach ($event->welcomeMembers as $welcomeMember) {
                            $welcomeMember->status = WelcomeLesson::STATUS_UNKNOWN;
                            $welcomeMember->save();
                        }
                    }
                    ComponentContainer::getActionLogger()->log(Action::TYPE_EVENT_PASSED, null, null, $event->course, $event->event_date);
                    break;
                case Event::STATUS_CANCELED:
                    if ($recalculateCharges) {
                        $event = EventComponent::addEvent($event->course, $event->eventDateTime);
                        if ($event) {
                            MoneyComponent::chargeByEvent($event);
                        }
                    }
                    foreach ($event->members as $member) {
                        $member->status = EventMember::STATUS_MISS;
                        $member->save();
                        if ($recalculateCharges) {
                            MoneyComponent::rechargeStudent($member->courseStudent->user, $event->course);
                        }
                        MoneyComponent::setUserChargeDates($member->courseStudent->user, $event->course);
                    }
                    foreach ($event->welcomeMembers as $welcomeMember) {
                        $welcomeMember->status = WelcomeLesson::STATUS_CANCELED;
                        $welcomeMember->save();
                    }
                    ComponentContainer::getActionLogger()->log(Action::TYPE_EVENT_CANCELLED, null, null, $event->course, $event->event_date);
                    break;
            }

            $transaction->commit();
            return self::getJsonOkResult(['eventData' => $this->prepareEventData($event)]);
        } catch (Throwable $ex) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('event/change-status', $ex->getMessage() . $ex->getTraceAsString(), true);
            return self::getJsonErrorResult($ex->getMessage());
        }
    }

    public function actionSetWelcomeMemberStatus(int $id)
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;
        $status = intval(Yii::$app->getRequest()->post('status'));
        
        $welcomeLesson = WelcomeLesson::findOne(['id' => $id, 'status' => WelcomeLesson::STATUS_UNKNOWN]);
        if (!$welcomeLesson) {
            return self::getJsonErrorResult('Student not found');
        }
        if (!in_array($status, [WelcomeLesson::STATUS_PASSED, WelcomeLesson::STATUS_MISSED])) {
            return self::getJsonErrorResult('Wrong status!');
        }
        
        $welcomeLesson->status = $status;
        if (!$welcomeLesson->save()) {
            ComponentContainer::getErrorLogger()
                ->logError('Event.setWelcomeStudentStatus', $welcomeLesson->getErrorsAsString(), true);
            return self::getJsonErrorResult();
        }
        ComponentContainer::getActionLogger()
            ->log(Action::TYPE_WELCOME_LESSON_STATUS_CHANGED, $welcomeLesson->user, null, $welcomeLesson->course, WelcomeLesson::STATUS_LABELS[$welcomeLesson->status]);

        return self::getJsonOkResult(['member' => $this->prepareWelcomeMemberData($welcomeLesson)]);
    }

    public function actionSetMemberStatus(int $id)
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;
        $status = intval(Yii::$app->getRequest()->post('status'));

        /** @var EventMember $member */
        if (!$member = EventMember::findOne($id)) {
            return self::getJsonErrorResult('Student not found');
        }
        if (!in_array($status, [EventMember::STATUS_ATTEND, EventMember::STATUS_MISS])) {
            return self::getJsonErrorResult('Wrong status!');
        }

        if ($status !== $member->status) {
            if (!Yii::$app->user->can($this->adminRule) &&
                (!$this->isTeacherHasAccess($member->event)
                    || $member->event->eventDateTime > $this->getLimitDate()
                    || ($member->status != EventMember::STATUS_UNKNOWN && ($member->status != EventMember::STATUS_MISS || $member->event->limitAttendTimestamp < time())))) {
                return self::getJsonErrorResult('Student edit denied!');
            }
            if (Yii::$app->user->can('teacher')
                && $member->event->teacherEditLimitDate < new DateTime()) {
                return self::getJsonErrorResult('Прошло слишком много времени после завершения занятия! Обратитесь в администрацию');
            }

            $member->status = $status;
            if ($member->status == EventMember::STATUS_MISS) {
                $member->mark = [];
            }
            if (!$member->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('Event.setStudentStatus', $member->getErrorsAsString(), true);
                return self::getJsonErrorResult();
            }

            if ($member->event->eventDateTime >= date_create('midnight') && !$member->attendance_notification_sent) {
                ComponentContainer::getBotPush()->attendance($member);
                $member->attendance_notification_sent = 1;
                $member->save();
            }
        }

        return self::getJsonOkResult(['member' => $this->prepareMemberData($member)]);
    }

    public function actionSetMark(int $memberId)
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var EventMember $member */
        $member = EventMember::findOne($memberId);
        $mark = Yii::$app->getRequest()->post('mark', []);
        if (!$member) {
            return self::getJsonErrorResult('Student not found');
        }
        if (empty($mark)) {
            return self::getJsonOkResult(['member' => $this->prepareMemberData($member)]);
        }
        if (!$this->isTeacherHasAccess($member->event) || $member->status != EventMember::STATUS_ATTEND) {
            return self::getJsonErrorResult('Mark set denied!');
        }
        if (Yii::$app->user->can('teacher') && $member->event->teacherEditLimitDate < new DateTime()) {
            return self::getJsonErrorResult('Прошло слишком много времени после завершения занятия! Обратитесь в администрацию');
        }
        $member->mark = array_merge($member->mark ?? [], $mark);

        if ($member->save()) {
            if (($member->mark[EventMember::MARK_LESSON] ?? 0) > 0 && $member->event->eventDateTime >= date_create('midnight') && !$member->mark_notification_sent) {
                ComponentContainer::getBotPush()->mark($member);
                $member->mark_notification_sent = 1;
                $member->save();
            }
            return self::getJsonOkResult(['member' => $this->prepareMemberData($member)]);
        } else {
            ComponentContainer::getErrorLogger()
                ->logError('Event.setMark', $member->getErrorsAsString(), true);
            return self::getJsonErrorResult();
        }
    }

    /**
     * Finds the Event model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Event the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Event::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    private function prepareEventData(Event $event): array
    {
        $members = [];
        foreach ($event->members as $member) {
            $members[] = $this->prepareMemberData($member);
        }
        $welcomeMembers = [];
        foreach ($event->welcomeMembers as $welcomeLesson) {
            $welcomeMembers[] = $this->prepareWelcomeMemberData($welcomeLesson);
        }
        return [
            'id' => $event->id,
            'status' => (int)$event->status,
            'time' => $event->eventTime,
            'limitAttendTimestamp' => $event->limitAttendTimestamp,
            'name' => $event->courseConfig->name,
            'teacher' => $event->courseConfig->teacher->name,
            'members' => $members,
            'welcomeMembers' => $welcomeMembers,
        ];
    }
    private function prepareMemberData(EventMember $member): array
    {
        return [
            'id' => $member->id,
            'status' => $member->status,
            'mark' => $member->mark,
            'courseStudent' => [
                'id' => $member->courseStudent->id,
                'debtMessage' => $member->courseStudent->paid_lessons < 0 ? 'долг ' . (0 - $member->courseStudent->paid_lessons) . ' ' . WordForm::getLessonsForm($member->courseStudent->paid_lessons) . '!' : null,
                'paidLessons' => $member->courseStudent->paid_lessons,
                'user' => [
                    'id' => $member->courseStudent->user->id,
                    'name' => $member->courseStudent->user->name,
                    'note' => $member->courseStudent->user->note,
                ]
            ]
        ];
    }
    private function prepareWelcomeMemberData(WelcomeLesson $welcomeLesson): array
    {
        return [
            'id' => $welcomeLesson->id,
            'status' => $welcomeLesson->status,
            'user' => [
                'id' => $welcomeLesson->user->id,
                'name' => $welcomeLesson->user->name,
                'note' => $welcomeLesson->user->note,
            ]
        ];
    }
}
