<?php

namespace backend\components;

use backend\models\Event;
use common\components\CourseComponent;
use common\components\MoneyComponent;
use common\models\Course;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Throwable;
use yii\base\Component;

class EventComponent extends Component
{
    /**
     * @param Course   $course
     * @param DateTime $date
     *
     * @return Event|null
     * @throws Exception
     * @throws Throwable
     */
    public static function addEvent(Course $course, DateTimeInterface $date): ?Event
    {
        $courseConfig = CourseComponent::getCourseConfig($course, $date);
        if ($courseConfig->hasLesson($date)) {
            if (!$event = $course->getEventByDate($date)) {
                $event = new Event();
                $event->event_date = $courseConfig->getLessonDateTime($date);
                $event->course_id = $course->id;
                $event->status = Event::STATUS_UNKNOWN;

                if (!$event->save()) {
                    throw new Exception('Schedule save error: ' . $event->getErrorsAsString());
                }
            }

            foreach ($course->courseStudents as $courseStudent) {
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
        } elseif ($course->hasEvent($date)) {
            $event = $course->getEventByDate($date);
            foreach ($event->membersWithPayments as $member) {
                foreach ($member->payments as $payment) {
                    MoneyComponent::cancelPayment($payment);
                }
                $event->removeCourseStudent($member->courseStudent);
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
        $limitDate = new DateTimeImmutable('+1 day midnight');
        $endDate = $course->endDateObject;
        if (!$endDate || $endDate > $limitDate) $endDate = $limitDate;
        $intervalDay = new DateInterval('P1D');
        for ($lookupDate = DateTime::createFromImmutable($course->startDateObject); $lookupDate < $endDate; $lookupDate->add($intervalDay)) {
            if ($course->courseStudents || $course->hasWelcomeLessons($lookupDate)) {
                $event = self::addEvent($course, $lookupDate);
                if ($event) {
                    MoneyComponent::chargeByEvent($event);
                }
            }
        }
        /** @var Event[] $overEvents */
        $overEvents = Event::find()
            ->andWhere(['course_id' => $course->id])
            ->andWhere([
                'or',
                ['<', 'event_date', $course->date_start],
                ['>', 'event_date', $endDate->format('Y-m-d H:i:s')]
            ])
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
