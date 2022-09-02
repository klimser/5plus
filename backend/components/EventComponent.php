<?php

namespace backend\components;

use backend\models\Event;
use common\components\CourseComponent;
use common\components\MoneyComponent;
use common\models\Course;
use DateInterval;
use DateTime;
use DateTimeInterface;
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
     * @param Course $course
     *
     * @throws Throwable
     */
    public static function fillSchedule(Course $course)
    {
        $limitDate = new \DateTimeImmutable('+1 day midnight');
        $lookupDate = DateTime::createFromImmutable($course->startDateObject);
        $endDate = $course->endDateObject;
        if (!$endDate || $endDate > $limitDate) $endDate = $limitDate;
        $intervalDay = new DateInterval('P1D');
        while ($lookupDate < $endDate) {
            if ($course->courseStudents || $course->hasWelcomeLessons($lookupDate)) {
                $event = self::addEvent($course, $lookupDate);
                if ($event) {
                    MoneyComponent::chargeByEvent($event);
                }
            }
            $lookupDate->add($intervalDay);
        }
        /** @var Event[] $overEvents */
        $overEvents = Event::find()
            ->andWhere(['course_id' => $course->id])
            ->andWhere(['>', 'event_date', $endDate->format('Y-m-d H:i:s')])
            ->with('members.payments')
            ->all();
        foreach ($overEvents as $event) {
            $event->status = Event::STATUS_CANCELED;
            MoneyComponent::chargeByEvent($event);
            if (!$event->delete()) throw new Exception('Unable to delete event');
        }
    }

    public static function getUncheckedEvent(Course $course, DateTimeInterface $limitDate): ?Event
    {
        /** @var Event|null $event */
        $event = Event::find()
            ->andWhere(['course_id' => $course->id, 'status' => Event::STATUS_UNKNOWN])
            ->andWhere(['<=', 'event_date', $limitDate->format('Y-m-d H:i:s')])
            ->orderBy(['event_date' => SORT_ASC])->one();
        return $event;
    }
}
