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
     * @param Course $course
     *
     * @throws \Exception
     */
    public static function calculateTeacherSalary(Course $course)
    {
        $monthInterval = new DateInterval('P1M');
        $limit = new DateTimeImmutable('first day of next month midnight');
        $to = $course->endDateObject;
        if ($to) {
            $to = $to->modify('first day of this month midnight')->add($monthInterval);
        }
        if (!$to || $to > $limit) $to = $limit;

        if ($course->groupPupils) {
            $dateStart = $course->startDateObject->modify('first day of this month midnight');
            $dateEnd = $dateStart->add($monthInterval);

            while ($dateEnd <= $to) {
                $groupParam = self::getGroupParam($course, $dateStart);

                $paymentSum = Payment::find()
                    ->andWhere(['<', 'amount', 0])
                    ->andWhere(['group_id' => $course->id])
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
            ->andWhere(['group_id' => $course->id])
            ->andWhere(['or', ['>', 'year', $to->format('Y')], ['and', ['year' => $to->format('Y')], ['>=', 'month', $to->format('n')]]])
            ->all();
        foreach ($overGroupParams as $overGroupParam) $overGroupParam->delete();
    }

    public static function addStudentToCourse(User $student, Course $course, DateTimeInterface $startDate, ?DateTimeInterface $endDate = null, bool $fillSchedule = true): CourseStudent
    {
        $startDate = (clone $startDate)->modify('midnight');
        if (!$startDate || ($endDate && $endDate < $startDate)) {
            throw new \Exception('Студент не добавлен в группу, введены некорректные значения даты начала и завершения занятий!');
        }
        if ($course->endDateObject && $startDate > $course->endDateObject) {
            throw new \Exception('Студент не добавлен в группу, выбрана дата начала занятий позже завершения занятий группы!');
        }
        self::checkStudentDates(null, $startDate, $endDate);
        $existingCourseStudent = CourseStudent::find()
            ->andWhere(['course_id' => $course->id, 'user_id' => $student->id])
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
        if ($existingCourseStudent) {
            throw new \Exception('Студент уже был добавлен в группу в выбранном промежутке времени, не добавляйте его дважды, так нельзя!');
        }

        $courseStudent = new CourseStudent();
        $courseStudent->user_id = $student->id;
        $courseStudent->course_id = $course->id;
        $courseStudent->date_start = $startDate < $course->startDateObject ? $course->date_start : $startDate->format('Y-m-d');
        if (null !== $endDate) {
            $endDate = (clone $endDate)->modify('midnight');
            if ($course->endDateObject && $endDate > $course->endDateObject) $endDate = $course->endDateObject;
            if ($endDate < $course->startDateObject) $endDate = $course->startDateObject;
            $courseStudent->date_end = $endDate->format('Y-m-d');
        }
        $dataForLog = $courseStudent->getDiffMap();
        if (!$courseStudent->save()) {
            ComponentContainer::getErrorLogger()
                ->logError('user/student-to-course', $courseStudent->getErrorsAsString(), true);
            throw new \Exception('Внутренняя ошибка сервера: ' . $courseStudent->getErrorsAsString());
        }

        if (!$student->save()) {
            ComponentContainer::getErrorLogger()
                ->logError('user/student-to-course', $student->getErrorsAsString(), true);
            throw new \Exception('Внутренняя ошибка сервера: ' . $student->getErrorsAsString());
        }

        $student->link('courseStudents', $courseStudent);
        $course->link('courseStudents', $courseStudent);

        if ($fillSchedule) {
            EventComponent::fillSchedule($course);
        }

        ComponentContainer::getActionLogger()->log(
            Action::TYPE_COURSE_STUDENT_ADDED,
            $courseStudent->user,
            null,
            $course,
            json_encode($dataForLog, JSON_UNESCAPED_UNICODE)
        );

        return $courseStudent;
    }

    public static function moveMoney(Course $courseFrom, Course $courseTo, User $user, ?DateTimeInterface $moveDate = null)
    {
        $moneyLeft = Payment::find()
            ->andWhere(['user_id' => $user->id, 'course_id' => $courseFrom->id])
            ->select('SUM(amount)')
            ->scalar();
        while ($moneyLeft > 0) {
            /** @var Payment $lastPayment */
            $lastPayment = Payment::find()
                ->andWhere(['user_id' => $user->id, 'course_id' => $courseFrom->id])
                ->andWhere(['>', 'amount', 0])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();
            if ($lastPayment->amount <= $moneyLeft) {
                $lastPayment->course_id = $courseTo->id;
                $lastPayment->save();
                $moneyLeft -= $lastPayment->amount;
            } else {
                $diff = $lastPayment->amount - $moneyLeft;
                MoneyComponent::decreasePayment($lastPayment, $diff);

                $newPayment = new Payment();
                $newPayment->user_id = $lastPayment->user_id;
                $newPayment->admin_id = Yii::$app->user->id;
                $newPayment->course_id = $courseTo->id;
                $newPayment->contract_id = $lastPayment->contract_id;
                $newPayment->amount = $moneyLeft;
                $newPayment->discount = $lastPayment->discount;
                $newPayment->created_at = $moveDate ? $moveDate->format('Y-m-d H:i:s') : $lastPayment->created_at;
                $newPayment->comment = 'Перевод оставшихся средств студента из группы ' . $courseFrom->courseConfig->name . ' в группу ' . $courseTo->courseConfig->name;
                MoneyComponent::registerIncome($newPayment);
                $moneyLeft = 0;
            }
        }
        EventComponent::fillSchedule($courseTo);
        MoneyComponent::rechargeStudent($user, $courseTo);
        MoneyComponent::setUserChargeDates($user, $courseFrom);
        MoneyComponent::setUserChargeDates($user, $courseTo);
        MoneyComponent::recalculateDebt($user, $courseTo);
    }

    public static function getStudentLimitDate(): ?DateTimeImmutable
    {
        return Yii::$app->user->can('pupilChangePast') ? null : new DateTimeImmutable('-7 days');
    }

    /**
     * @param CourseStudent|null     $courseStudent
     * @param DateTimeInterface      $startDate
     * @param DateTimeInterface|null $endDate
     *
     * @throws \Exception
     */
    public static function checkStudentDates(?CourseStudent $courseStudent, DateTimeInterface $startDate, ?DateTimeInterface $endDate)
    {
        $limitDate = self::getStudentLimitDate();
        if ($limitDate && (($courseStudent && $courseStudent->startDateObject !== $startDate && ($courseStudent->startDateObject < $limitDate || $startDate < $limitDate))
            || (!$courseStudent && $startDate < $limitDate)
            || (!$courseStudent && $endDate && $endDate < $limitDate)
            || ($courseStudent && $courseStudent->endDateObject !== $endDate
                && (
                    ($courseStudent->endDateObject && $courseStudent->endDateObject < $limitDate)
                    || ($endDate && $endDate < $limitDate)
                )
            ))) {
            throw new \Exception('Дата занятий студента ' . ($courseStudent ? $courseStudent->user->name : '') . ' может быть изменена только Александром Сергеевичем, обратитесь к нему.');
        }
    }
}
