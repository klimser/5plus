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
            $groupParam = CourseComponent::getGroupParam($event->group, new DateTime());
            return $groupParam->teacher_id == Yii::$app->user->identity->teacher_id;
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
            ->where('event_date > :startDate', [':startDate' => $startDate->format('Y-m-d H:i:s')])
            ->andWhere('event_date < :endDate', [':endDate' => $endDate->format('Y-m-d H:i:s')])
            ->with(['group', 'members.groupPupil.user', 'welcomeMembers'])
            ->orderBy(['event_date' => SORT_ASC]);
        
        if (Yii::$app->user->can('teacher')) {
            $teacherId = Yii::$app->user->identity->teacher_id;
            $eventsQuery->joinWith(['group' => function(yii\db\ActiveQuery $query) { $query->alias('g'); }])
                ->leftJoin(
                    ['gp' => GroupParam::tableName()],
                    'g.id = gp.group_id AND gp.year = :year AND gp.month = :month',
                    [':year' => (int)$startDate->format('Y'), ':month' => (int)$startDate->format('n')]
                )
                ->andWhere([
                    'or',
                    ['gp.teacher_id' => $teacherId],
                    ['and', ['gp.id' => null], ['g.teacher_id' => $teacherId]]
                ]);
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
            ->with(['members.groupPupil.user', 'welcomeMembers.user'])
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
        $event = Event::find()->andWhere(['id' => $id])->with('members.groupPupil.user')->one();
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
                ComponentContainer::getActionLogger()->log(Action::TYPE_EVENT_STATUS_REVERTED, null, null, $event->group);
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
                        MoneyComponent::setUserChargeDates($member->groupPupil->user, $event->group);
                        if ($member->groupPupil->user->getDebt($member->groupPupil->group)) {
                            ComponentContainer::getBotPush()->lowBalance($member->groupPupil);
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
                    ComponentContainer::getActionLogger()->log(Action::TYPE_EVENT_PASSED, null, null, $event->group);
                    break;
                case Event::STATUS_CANCELED:
                    if ($recalculateCharges) {
                        EventComponent::fillSchedule($event->group);
                    }
                    foreach ($event->members as $member) {
                        $member->status = EventMember::STATUS_MISS;
                        $member->save();
                        if ($recalculateCharges) {
                            MoneyComponent::rechargePupil($member->groupPupil->user, $event->group);
                        }
                        MoneyComponent::setUserChargeDates($member->groupPupil->user, $event->group);
                    }
                    foreach ($event->welcomeMembers as $welcomeMember) {
                        $welcomeMember->status = WelcomeLesson::STATUS_CANCELED;
                        $welcomeMember->save();
                    }
                    ComponentContainer::getActionLogger()->log(Action::TYPE_EVENT_CANCELLED, null, null, $event->group);
                    break;
            }

            $transaction->commit();
            return self::getJsonOkResult(['eventData' => $this->prepareEventData($event)]);
        } catch (Throwable $ex) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('event/change-status', $ex->getMessage(), true);
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
                ->logError('Event.setWelcomePupilStatus', $welcomeLesson->getErrorsAsString(), true);
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
                $member->mark = null;
                $member->mark_homework = null;
            }
            if (!$member->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('Event.setPupilStatus', $member->getErrorsAsString(), true);
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
        $mark = intval(Yii::$app->getRequest()->post('mark'));
        $markHomework = intval(Yii::$app->getRequest()->post('mark_homework'));
        if (!$member) {
            return self::getJsonErrorResult('Student not found');
        }
        if (!$this->isTeacherHasAccess($member->event) || $member->status != EventMember::STATUS_ATTEND) {
            return self::getJsonErrorResult('Mark set denied!');
        }
        if (Yii::$app->user->can('teacher') && $member->event->teacherEditLimitDate < new DateTime()) {
            return self::getJsonErrorResult('Прошло слишком много времени после завершения занятия! Обратитесь в администрацию');
        }
        if ($mark > 0 && $mark <= 5) {
            $member->mark = $mark;
        }
        if ($markHomework > 0 && $markHomework <= 5) {
            $member->mark_homework = $markHomework;
        }

        if ($member->save()) {
            if ($member->mark > 0 && $member->event->eventDateTime >= date_create('midnight') && !$member->mark_notification_sent) {
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
            'name' => $event->group->name,
            'teacher' => $event->teacher->name,
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
            'markHomework' => $member->mark_homework,
            'groupPupil' => [
                'id' => $member->groupPupil->id,
                'debtMessage' => $member->groupPupil->paid_lessons < 0 ? 'долг ' . (0 - $member->groupPupil->paid_lessons) . ' ' . WordForm::getLessonsForm($member->groupPupil->paid_lessons) . '!' : null,
                'paidLessons' => $member->groupPupil->paid_lessons,
                'user' => [
                    'id' => $member->groupPupil->user->id,
                    'name' => $member->groupPupil->user->name,
                    'note' => $member->groupPupil->user->note,
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
