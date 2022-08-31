<?php

namespace backend\components;

use backend\models\Event;
use common\components\CourseComponent;
use common\components\MoneyComponent;
use common\models\Course;
use DateInterval;
use DateTime;
use Exception;
use Throwable;
use yii\base\Component;

class EventComponent extends Component
{
    /**
     * @param Course   $group
     * @param DateTime $date
     *
     * @return Event|null
     * @throws Exception
     * @throws Throwable
     */
    public static function addEvent(Course $group, DateTime $date): ?Event
    {
        $groupConfig = CourseComponent::getCourseConfig($group, $date);
        if ($groupConfig->hasLesson($date)) {
            if (!$event = $group->getEventByDate($date)) {
                $event = new Event();
                $event->event_date = $groupConfig->getLessonDateTime($date);
                $event->course_id = $group->id;
                $event->status = Event::STATUS_UNKNOWN;

                if (!$event->save()) {
                    throw new Exception('Schedule save error: ' . $event->getErrorsAsString());
                }
            }

            foreach ($group->courseStudents as $courseStudent) {
                if ($courseStudent->startDateObject <= $event->eventDateTime && ($courseStudent->date_end == null || $courseStudent->endDateObject > $event->eventDateTime)) {
                    $event->addCourseStudent($courseStudent);
                } elseif ($eventMember = $event->findByCourseStudent($courseStudent)) {
                    foreach ($eventMember->payments as $payment) {
                        MoneyComponent::cancelPayment($payment);
                    }
                    $event->removeCourseStudent($courseStudent);
                }
            }

            return $event;
        } elseif ($group->hasEvent($date)) {
            $event = $group->getEventByDate($date);
            foreach ($event->membersWithPayments as $member) {
                foreach ($member->payments as $payment) {
                    MoneyComponent::cancelPayment($payment);
                }
                $event->removeCourseStudent($member->groupPupil);
            }
            $event->delete();
        }

        return null;
    }

    /**
     * @param Course $group
     *
     * @throws Throwable
     */
    public static function fillSchedule(Course $group)
    {
        $limitDate = new \DateTimeImmutable('+1 day midnight');
        $lookupDate = DateTime::createFromImmutable($group->startDateObject);
        $endDate = $group->endDateObject;
        if (!$endDate || $endDate > $limitDate) $endDate = $limitDate;
        $intervalDay = new DateInterval('P1D');
        while ($lookupDate < $endDate) {
            if ($group->groupPupils || $group->hasWelcomeLessons($lookupDate)) {
                $event = self::addEvent($group, $lookupDate);
                if ($event) {
                    MoneyComponent::chargeByEvent($event);
                }
            }
            $lookupDate->add($intervalDay);
        }
        /** @var Event[] $overEvents */
        $overEvents = Event::find()
            ->andWhere(['group_id' => $group->id])
            ->andWhere(['>', 'event_date', $endDate->format('Y-m-d H:i:s')])
            ->with('members.payments')
            ->all();
        foreach ($overEvents as $event) {
            $event->status = Event::STATUS_CANCELED;
            MoneyComponent::chargeByEvent($event);
            if (!$event->delete()) throw new Exception('Unable to delete event');
        }
    }

    /**
     * @param Course             $group
     * @param \DateTimeInterface $limitDate
     *
     * @return Event|null
     */
    public static function getUncheckedEvent(Course $group, \DateTimeInterface $limitDate): ?Event
    {
        /** @var Event|null $event */
        $event = Event::find()
            ->andWhere(['group_id' => $group->id, 'status' => Event::STATUS_UNKNOWN])
            ->andWhere(['<=', 'event_date', $limitDate->format('Y-m-d H:i:s')])
            ->orderBy(['event_date' => SORT_ASC])->one();
        return $event;
    }
}
