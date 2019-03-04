<?php

namespace backend\components;

use backend\models\Event;
use common\components\GroupComponent;
use common\components\MoneyComponent;
use common\models\Group;
use yii\base\Component;

class EventComponent extends Component
{
    /**
     * @param Group $group
     * @param \DateTime $date
     * @return Event|null
     * @throws \Exception
     * @throws \Throwable
     */
    public static function addEvent(Group $group, \DateTime $date): ?Event
    {
        $groupParam = GroupComponent::getGroupParam($group, $date);
        if ($groupParam->isHasLesson($date)) {
            $event = $group->hasEvent($date);
            if (!$event) {
                $event = new Event();
                $event->event_date = $groupParam->getLessonDateTime($date);
                $event->group_id = $group->id;
                $event->status = Event::STATUS_UNKNOWN;

                if (!$event->save()) throw new \Exception('Schedule save error: ' . $event->getErrorsAsString());
            }

            foreach ($group->groupPupils as $groupPupil) {
                if ($groupPupil->startDateObject <= $event->eventDateTime && ($groupPupil->date_end == null || $groupPupil->endDateObject >= $event->eventDateTime)) {
                    $event->addGroupPupil($groupPupil);
                } elseif ($eventMember = $event->hasGroupPupil($groupPupil)) {
                    if ($eventMember->payments) {
                        foreach ($eventMember->payments as $payment) MoneyComponent::cancelPayment($payment);
                    }
                    $event->removeGroupPupil($groupPupil);
                }
            }
            return $event;
        } elseif ($event = $group->hasEvent($date)) {
            foreach ($event->members as $member) {
                if ($member->payments) {
                    foreach ($member->payments as $payment) MoneyComponent::cancelPayment($payment);
                }
                $event->removeGroupPupil($member->groupPupil);
            }
            $event->delete();
        }
        return null;
    }

    /**
     * @param Event $event
     * @return bool
     */
    private static function deleteEvent(Event $event): bool
    {
        foreach ($event->members as $eventMember) if (!$eventMember->delete()) return false;
        return boolval($event->delete());
    }

    /**
     * @param Group $group
     * @throws \Exception
     */
    public static function fillSchedule(Group $group)
    {
        $limitDate = new \DateTime('+1 day midnight');
        if ($group->groupPupils) {
            $startDate = clone $group->startDateObject;
            $startDate->modify('midnight');
            $endDate = $group->endDateObject ? clone $group->endDateObject : null;
            if (!$endDate || $endDate > $limitDate) $endDate = $limitDate;
            $endDate->modify('midnight');
            $intervalDay = new \DateInterval('P1D');
            while ($startDate <= $endDate) {
                $event = self::addEvent($group, $startDate);
                if ($event) MoneyComponent::chargeByEvent($event);
                $startDate->add($intervalDay);
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
                if (!self::deleteEvent($event)) throw new \Exception('Unable to delete event');
            }
        }
    }
}