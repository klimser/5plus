<?php

namespace backend\controllers;

use common\components\ComponentContainer;
use common\components\GroupComponent;
use common\components\MoneyComponent;
use backend\models\Event;
use backend\models\EventMember;
use yii;
use yii\web\NotFoundHttpException;

class EventController extends AdminController
{
    protected $accessRule = 'manageSchedule';

    private function getLimitDate(): \DateTime
    {
        return new \DateTime('+30 minute');
    }

    /**
     * Lists all Page models.
     * @return mixed
     */
    public function actionIndex()
    {
        $eventsDate = Yii::$app->request->getQueryParam('date');
        if ($eventsDate) {
            $startDate = new \DateTime($eventsDate);
        } else {
            $startDate = new \DateTime();
        }
        $startDate->modify('midnight');
        $endDate = clone($startDate);
        $endDate->modify('+1 day');

        $events = Event::find()
            ->where('event_date > :startDate', [':startDate' => $startDate->format('Y-m-d H:i:s')])
            ->andWhere('event_date < :endDate', [':endDate' => $endDate->format('Y-m-d H:i:s')])
            ->with(['group', 'members.groupPupil.user'])
            ->orderBy(['event_date' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'events' => $events,
            'limitDate' => $this->getLimitDate(),
        ]);
    }

    /**
     * @param int $event
     * @return yii\web\Response
     * @throws yii\web\BadRequestHttpException
     */
    public function actionChangeStatus($event)
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        /** @var Event $event */
        $event = Event::find()->andWhere(['id' => $event])->with('members.groupPupil.user')->one();
        if (!$event) $jsonData = self::getJsonErrorResult('Event not found');
        else {
            $status = Yii::$app->getRequest()->post('status');
            if ($event->status != Event::STATUS_UNKNOWN || $event->eventDateTime > $this->getLimitDate()) $jsonData = self::getJsonErrorResult('Event edit denied!');
            elseif (!in_array($status, [Event::STATUS_PASSED, Event::STATUS_CANCELED])) $jsonData = self::getJsonErrorResult('Wrong status!');
            else {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    $event->status = $status;
                    if (!$event->save()) {
                        ComponentContainer::getErrorLogger()
                            ->logError('event/change-status', $event->getErrorsAsString(), true);
                        throw new \Exception('Server error');
                    } else {
                        switch ($status) {
                            case Event::STATUS_PASSED:
                                MoneyComponent::chargeByEvent($event);
                                GroupComponent::calculateTeacherSalary($event->group);

                                foreach ($event->members as $member) {
                                    MoneyComponent::setUserChargeDates($member->groupPupil->user, $event->group);
                                }
                                break;
                            case Event::STATUS_CANCELED:
                                foreach ($event->members as $member) {
                                    $member->status = EventMember::STATUS_MISS;
                                    $member->save();
                                }
                                break;
                        }

                        $transaction->commit();
                        $jsonData = self::getJsonOkResult([
                            'eventId' => $event->id,
                            'eventStatus' => $event->status,
                        ]);
                    }
                } catch (\Throwable $ex) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()
                        ->logError('event/change-status', $ex->getMessage(), true);
                    $jsonData = self::getJsonErrorResult($ex->getMessage());
                }
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * @param int $memberId
     * @return yii\web\Response
     * @throws yii\web\BadRequestHttpException
     */
    public function actionSetPupilStatus($memberId)
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        /** @var EventMember $eventMember */
        $eventMember = EventMember::findOne($memberId);
        if (!$eventMember) $jsonData = self::getJsonErrorResult('Pupil not found');
        else {
            $status = Yii::$app->getRequest()->post('status');
            if (!in_array($status, [EventMember::STATUS_ATTEND, EventMember::STATUS_MISS])) $jsonData = self::getJsonErrorResult('Wrong status!');
            elseif ($eventMember->event->eventDateTime > $this->getLimitDate()
                || ($eventMember->status != EventMember::STATUS_UNKNOWN && ($eventMember->status != EventMember::STATUS_MISS || $eventMember->event->limitAttendTimestamp < time()))) {
                $jsonData = self::getJsonErrorResult('Pupil edit denied!');
            } else {
                $eventMember->status = $status;
                if ($eventMember->save()) {
                    $jsonData = self::getJsonOkResult([
                        'memberId' => $eventMember->id,
                        'memberStatus' => $eventMember->status,
                    ]);
                } else {
                    ComponentContainer::getErrorLogger()
                        ->logError('Event.setPupilStatus', $eventMember->getErrorsAsString(), true);
                    $jsonData = self::getJsonErrorResult('Server error');
                }
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * @param int $member
     * @return yii\web\Response
     * @throws yii\web\BadRequestHttpException
     */
    public function actionSetPupilMark($member)
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        /** @var EventMember $eventMember */
        $eventMember = EventMember::findOne($member);
        $mark = intval(Yii::$app->getRequest()->post('mark'));
        if (!$eventMember) $jsonData = self::getJsonErrorResult('Pupil not found');
        elseif ($eventMember->status != EventMember::STATUS_ATTEND) $jsonData = self::getJsonErrorResult('Pupil edit denied!');
        elseif ($mark <= 0 || $mark > 5) $jsonData = self::getJsonErrorResult('Wrong mark!');
        else {
            $eventMember->mark = $mark;
            if ($eventMember->save()) {
                $jsonData = self::getJsonOkResult([
                    'memberId' => $eventMember->id,
                    'memberMark' => $eventMember->mark,
                ]);
            } else {
                ComponentContainer::getErrorLogger()
                    ->logError('Event.setPupilMark', $eventMember->getErrorsAsString(), true);
                $jsonData = self::getJsonErrorResult();
            }
        }

        return $this->asJson($jsonData);
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
}
