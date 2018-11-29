<?php

namespace common\components;

use backend\components\EventComponent;
use common\models\Group;
use common\models\GroupParam;
use common\models\GroupPupil;
use common\models\Payment;
use common\models\User;
use yii\base\Component;

class GroupComponent extends Component
{
    /**
     * @param Group $group
     * @param \DateTime $date
     * @return GroupParam
     * @throws \Exception
     */
    public static function getGroupParam(Group $group, \DateTime $date): GroupParam
    {
        $groupParam = GroupParam::findByDate($group, $date);
        if (!$groupParam) {
            $groupParam = new GroupParam();
            $groupParam->group_id = $group->id;
            $groupParam->year = $date->format('Y');
            $groupParam->month = $date->format('n');
            $groupParam->lesson_price = $group->lesson_price;
            $groupParam->lesson_price_discount = $group->lesson_price_discount;
            $groupParam->teacher_id = $group->teacher_id;
            $groupParam->weekday = $group->weekday;
            $groupParam->schedule = $group->schedule;
            $groupParam->teacher_rate = $group->teacher_rate;
            if (!$groupParam->save()) throw new \Exception('Unable to save group param: ' . $groupParam->getErrorsAsString());
        }
        return $groupParam;
    }

    /**
     * @param string $scheduleString
     * @return int
     */
    public static function getWeekClasses(string $scheduleString): int
    {
        if (!preg_match('#^[01]{6}$#', $scheduleString)) throw new \InvalidArgumentException('Wrong schedule string');

        return substr_count($scheduleString, '1');
    }

    /**
     * @param string $scheduleString - string of length 6 contains only 0 and 1
     * @return int
     */
    public static function getTotalClasses(string $scheduleString): int
    {
        if (!preg_match('#^[01]{6}$#', $scheduleString)) throw new \InvalidArgumentException('Wrong schedule string');

        $classPerWeek = substr_count($scheduleString, '1');
        return $classPerWeek * 4;
    }

    /**
     * @param Group $group
     * @throws \Exception
     */
    public static function calculateTeacherSalary(Group $group)
    {
        $monthInterval = new \DateInterval('P1M');
        $limit = new \DateTime('first day of next month midnight');
        $to = $group->endDateObject;
        if ($to) {
            $to = clone($to);
            $to->modify('first day of this month midnight');
            $to->add($monthInterval);
        }
        if (!$to || $to > $limit) $to = $limit;

        if ($group->groupPupils) {
            $dateStart = clone $group->startDateObject;
            $dateStart->modify('first day of this month midnight');
            $dateEnd = clone $dateStart;
            $dateEnd->add($monthInterval);

            while ($dateEnd <= $to) {
                $groupParam = self::getGroupParam($group, $dateStart);

                $paymentSum = Payment::find()
                    ->andWhere(['<', 'amount', 0])
                    ->andWhere(['group_id' => $group->id])
                    ->andWhere(['>=', 'created_at', $dateStart->format('Y-m-d H:i:s')])
                    ->andWhere(['<', 'created_at', $dateEnd->format('Y-m-d H:i:s')])
                    ->select('SUM(amount)')
                    ->scalar();
                $groupParam->teacher_salary = round($paymentSum * (-1) * $groupParam->teacher_rate / 100);
                if (!$groupParam->save()) throw new \Exception($groupParam->getErrorsAsString());

                $dateStart->add($monthInterval);
                $dateEnd->add($monthInterval);
            }
        }
        /** @var GroupParam $minGroupParam */
        $overGroupParams = GroupParam::find()
            ->andWhere(['group_id' => $group->id])
            ->andWhere(['or', ['>', 'year', $to->format('Y')], ['and', ['year' => $to->format('Y')], ['>=', 'month', $to->format('n')]]])
            ->all();
        foreach ($overGroupParams as $overGroupParam) $overGroupParam->delete();
    }

    /**
     * @param User $pupil
     * @param Group $group
     * @param \DateTime $startDate
     * @param \DateTime|null $endDate
     * @return GroupPupil
     * @throws \Exception
     */
    public static function addPupilToGroup(User $pupil, Group $group, \DateTime $startDate, ?\DateTime $endDate = null): GroupPupil
    {
        if (!$group || !$startDate || ($endDate && $endDate < $startDate)) {
            throw new \Exception('Ученик не добавлен в группу, введены некорректные значения даты начала и завершения занятий!');
        } else {
            $startDate->modify('midnight');
            if ($group->endDateObject && $startDate > $group->endDateObject) {
                throw new \Exception('Ученик не добавлен в группу, выбрана дата начала занятий позже завершения занятий группы!');
            } else {
                $groupPupil = new GroupPupil();
                $groupPupil->user_id = $pupil->id;
                $groupPupil->group_id = $group->id;
                $groupPupil->date_start = $startDate < $group->startDateObject ? $group->date_start : $startDate->format('Y-m-d');
                if ($endDate) {
                    $endDate->modify('midnight');
                    if ($group->endDateObject && $endDate > $group->endDateObject) $endDate = $group->endDateObject;
                    if ($endDate < $group->startDateObject) $endDate = $group->startDateObject;
                    $groupPupil->date_end = $endDate->format('Y-m-d');
                }
                if (!$groupPupil->save()) {
                    \Yii::$app->errorLogger->logError('user/pupil-to-group', $groupPupil->getErrorsAsString(), true);
                    throw new \Exception('Внутренняя ошибка сервера: ' . $groupPupil->getErrorsAsString());
                } else {
                    $pupil->link('groupPupils', $groupPupil);
                    $group->link('groupPupils', $groupPupil);

                    EventComponent::fillSchedule($group);
                    GroupComponent::calculateTeacherSalary($group);

                    return $groupPupil;
                }
            }
        }
    }
}