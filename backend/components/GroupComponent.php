<?php

namespace backend\components;

use backend\models\Event;
use backend\models\Group;
use backend\models\GroupParam;
use backend\models\GroupPupil;
use backend\models\Payment;
use yii\base\Component;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

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
            $to->modify('first day of ' . $to->format('F Y') . ' midnight');
            $to->add($monthInterval);
        }
        if (!$to || $to > $limit) $to = $limit;

        if ($group->groupPupils) {
            $dateStart = clone $group->startDateObject;
            $dateStart->modify('first day of ' . $dateStart->format('F Y') . ' midnight');
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
}