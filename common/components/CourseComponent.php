<?php

namespace common\components;

use backend\components\EventComponent;
use common\models\Course;
use common\models\CourseConfig;
use common\models\GroupParam;
use common\models\CourseStudent;
use common\models\Payment;
use common\models\User;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Yii;
use yii\base\Component;

class CourseComponent extends Component
{
    /**
     * @return array<Course>
     */
    public static function getAllSortedByActiveAndName(): array
    {
        /** @var Course[] $courses */
        $courses = Course::findAll([]);
        usort(
            $courses,
            static fn (Course $a, Course $b) => (0 === ($res = $a->active <=> $b->active) ? $a->courseConfig->name <=> $b->courseConfig->name : $res)
        );

        return $courses;
    }

    /**
     * @return array<Course>
     */
    public static function getActiveSortedByName(): array
    {
        /** @var Course[] $courses */
        $courses = Course::findAll(['active' => Course::STATUS_ACTIVE]);
        usort(
            $courses,
            static fn (Course $a, Course $b) => $a->courseConfig->name <=> $b->courseConfig->name
        );

        return $courses;
    }
    /**
     * @param Course    $group
     * @param \DateTime $date
     *
     * @return GroupParam
     * @throws \Exception
     */
    public static function getGroupParam(Course $group, DateTimeInterface $date): GroupParam
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
            $groupParam->schedule = $group->schedule;
            $groupParam->teacher_rate = $group->teacher_rate;
            if (!$groupParam->save()) throw new \Exception('Unable to save group param: ' . $groupParam->getErrorsAsString());
        }
        return $groupParam;
    }

    public static function getCourseConfig(Course $course, DateTimeInterface $date): CourseConfig
    {
        $courseConfig = CourseConfig::findByDate($course, $date);
        if (!$courseConfig) {
            throw new \Exception(sprintf('No course config: Course ID %s, date %s ', $course->id, $date->format('d.m.Y')));
        }
        return $courseConfig;
    }

    /**
     * @param Course $group
     *
     * @throws \Exception
     */
    public static function calculateTeacherSalary(Course $group)
    {
        $monthInterval = new DateInterval('P1M');
        $limit = new DateTimeImmutable('first day of next month midnight');
        $to = $group->endDateObject;
        if ($to) {
            $to = $to->modify('first day of this month midnight')->add($monthInterval);
        }
        if (!$to || $to > $limit) $to = $limit;

        if ($group->groupPupils) {
            $dateStart = $group->startDateObject->modify('first day of this month midnight');
            $dateEnd = $dateStart->add($monthInterval);

            while ($dateEnd <= $to) {
                $groupParam = self::getGroupParam($group, $dateStart);

                $paymentSum = Payment::find()
                    ->andWhere(['<', 'amount', 0])
                    ->andWhere(['group_id' => $group->id])
                    ->andWhere(['>=', 'created_at', $dateStart->format('Y-m-d H:i:s')])
                    ->andWhere(['<', 'created_at', $dateEnd->format('Y-m-d H:i:s')])
                    ->andWhere('used_payment_id IS NOT NULL')
                    ->select('SUM(amount)')
                    ->scalar();
                $groupParam->teacher_salary = round($paymentSum * (-1) * $groupParam->teacher_rate / 100);
                if (!$groupParam->save()) throw new \Exception($groupParam->getErrorsAsString());

                $dateStart = $dateStart->add($monthInterval);
                $dateEnd = $dateEnd->add($monthInterval);
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
     * @param User           $pupil
     * @param Course         $group
     * @param \DateTime      $startDate
     * @param \DateTime|null $endDate
     * @param bool           $fillSchedule
     *
     * @return CourseStudent
     * @throws \Exception
     */
    public static function addPupilToGroup(User $pupil, Course $group, DateTimeInterface $startDate, ?DateTimeInterface $endDate = null, bool $fillSchedule = true): CourseStudent
    {
        $startDate = (clone $startDate)->modify('midnight');
        if (!$group || !$startDate || ($endDate && $endDate < $startDate)) {
            throw new \Exception('Студент не добавлен в группу, введены некорректные значения даты начала и завершения занятий!');
        }
        if ($group->endDateObject && $startDate > $group->endDateObject) {
            throw new \Exception('Студент не добавлен в группу, выбрана дата начала занятий позже завершения занятий группы!');
        }
        self::checkPupilDates(null, $startDate, $endDate);
        $existingGroupPupil = CourseStudent::find()
            ->andWhere(['group_id' => $group->id, 'user_id' => $pupil->id])
            ->andWhere(['OR',
                ['AND',
                    ['<', 'date_start', $startDate->format('Y-m-d')],
                    ['OR', ['date_end' => null], ['>', 'date_end', $startDate->format('Y-m-d')]]],
                ['AND',
                    ['>=', 'date_start', $startDate->format('Y-m-d')],
                    ($endDate ? ['<=', 'date_start', $endDate->format('Y-m-d')] : '1'),
                ]
            ])
            ->one();
        if ($existingGroupPupil) {
            throw new \Exception('Студент уже был добавлен в группу в выбранном промежутке времени, не добавляйте его дважды, так нельзя!');
        }

        $groupPupil = new CourseStudent();
        $groupPupil->user_id = $pupil->id;
        $groupPupil->group_id = $group->id;
        $groupPupil->date_start = $startDate < $group->startDateObject ? $group->date_start : $startDate->format('Y-m-d');
        if (null !== $endDate) {
            $endDate = (clone $endDate)->modify('midnight');
            if ($group->endDateObject && $endDate > $group->endDateObject) $endDate = $group->endDateObject;
            if ($endDate < $group->startDateObject) $endDate = $group->startDateObject;
            $groupPupil->date_end = $endDate->format('Y-m-d');
        }
        $dataForLog = $groupPupil->getDiffMap();
        if (!$groupPupil->save()) {
            ComponentContainer::getErrorLogger()
                ->logError('user/pupil-to-group', $groupPupil->getErrorsAsString(), true);
            throw new \Exception('Внутренняя ошибка сервера: ' . $groupPupil->getErrorsAsString());
        }

        if (!$pupil->save()) {
            ComponentContainer::getErrorLogger()
                ->logError('user/pupil-to-group', $pupil->getErrorsAsString(), true);
            throw new \Exception('Внутренняя ошибка сервера: ' . $pupil->getErrorsAsString());
        }

        $pupil->link('groupPupils', $groupPupil);
        $group->link('groupPupils', $groupPupil);

        if ($fillSchedule) {
            EventComponent::fillSchedule($group);
            CourseComponent::calculateTeacherSalary($group);
        }

        ComponentContainer::getActionLogger()->log(
            Action::TYPE_GROUP_PUPIL_ADDED,
            $groupPupil->user,
            null,
            $group,
            json_encode($dataForLog, JSON_UNESCAPED_UNICODE)
        );

        return $groupPupil;
    }

    /**
     * @param Course $groupFrom
     * @param Course $groupTo
     * @param User $user
     * @param \DateTime|null $moveDate
     */
    public static function moveMoney(Course $groupFrom, Course $groupTo, User $user, ?DateTimeInterface $moveDate = null)
    {
        $moneyLeft = Payment::find()
            ->andWhere(['user_id' => $user->id, 'group_id' => $groupFrom->id])
            ->select('SUM(amount)')
            ->scalar();
        while ($moneyLeft > 0) {
            /** @var Payment $lastPayment */
            $lastPayment = Payment::find()
                ->andWhere(['user_id' => $user->id, 'group_id' => $groupFrom->id])
                ->andWhere(['>', 'amount', 0])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();
            if ($lastPayment->amount <= $moneyLeft) {
                $lastPayment->group_id = $groupTo->id;
                $lastPayment->save();
                $moneyLeft -= $lastPayment->amount;
            } else {
                $diff = $lastPayment->amount - $moneyLeft;
                MoneyComponent::decreasePayment($lastPayment, $diff);

                $newPayment = new Payment();
                $newPayment->user_id = $lastPayment->user_id;
                $newPayment->admin_id = Yii::$app->user->id;
                $newPayment->group_id = $groupTo->id;
                $newPayment->contract_id = $lastPayment->contract_id;
                $newPayment->amount = $moneyLeft;
                $newPayment->discount = $lastPayment->discount;
                $newPayment->created_at = $moveDate ? $moveDate->format('Y-m-d H:i:s') : $lastPayment->created_at;
                $newPayment->comment = 'Перевод оставшихся средств студента из группы ' . $groupFrom->name . ' в группу ' . $groupTo->name;
                MoneyComponent::registerIncome($newPayment);
                $moneyLeft = 0;
            }
        }
        EventComponent::fillSchedule($groupTo);
        MoneyComponent::rechargePupil($user, $groupTo);
        CourseComponent::calculateTeacherSalary($groupTo);
        MoneyComponent::setUserChargeDates($user, $groupFrom);
        MoneyComponent::setUserChargeDates($user, $groupTo);
        MoneyComponent::recalculateDebt($user, $groupTo);
    }

    public static function getPupilLimitDate(): ?\DateTime
    {
        return Yii::$app->user->can('pupilChangePast') ? null : new \DateTime('-7 days');
    }

    /**
     * @param CourseStudent|null     $groupPupil
     * @param DateTimeInterface      $startDate
     * @param DateTimeInterface|null $endDate
     *
     * @throws \Exception
     */
    public static function checkPupilDates(?CourseStudent $groupPupil, DateTimeInterface $startDate, ?DateTimeInterface $endDate)
    {
        $limitDate = self::getPupilLimitDate();
        if ($limitDate && (($groupPupil && $groupPupil->startDateObject != $startDate && ($groupPupil->startDateObject < $limitDate || $startDate < $limitDate))
            || (!$groupPupil && $startDate < $limitDate)
            || ($groupPupil && $groupPupil->endDateObject != $endDate
                && ($groupPupil->endDateObject && $groupPupil->endDateObject < $limitDate)
                || ($endDate && $endDate < $limitDate))
            || (!$groupPupil && $endDate && $endDate < $limitDate))) {
            throw new \Exception('Дата занятий студента ' . ($groupPupil ? $groupPupil->user->name : '') . ' может быть изменена только Александром Сергеевичем, обратитесь к нему.');
        }
    }
}
