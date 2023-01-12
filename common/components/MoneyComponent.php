<?php

namespace common\components;

use backend\models\Event;
use backend\models\EventMember;
use common\components\helpers\MoneyHelper;
use common\models\Company;
use common\models\Contract;
use common\models\Course;
use common\models\CourseConfig;
use common\models\CourseStudent;
use common\models\Debt;
use common\models\Payment;
use common\models\User;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Yii;
use yii\base\Component;
use yii\db\ActiveQuery;

class MoneyComponent extends Component
{
    protected static function addStudentMoney(User $student, int $amount, Course $course)
    {
        if ($student->role != User::ROLE_STUDENT) throw new \Exception('Money can be changed only for students');

        $student->money += $amount;
        if (!$student->save(true, ['money'])) throw new \Exception($student->getErrorsAsString());
        if (!self::recalculateDebt($student, $course)) throw new \Exception('Error on student\'s debt calculation');
    }

    public static function registerIncome(Payment $payment): int
    {
        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            self::savePayment($payment, Action::TYPE_INCOME, !empty(Yii::$app->user->id));
            self::rechargeStudent($payment->user, $payment->course);
            $transaction->commit();
            return $payment->id;
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('money/income', $ex->getMessage(), true);
            throw $ex;
        }
    }

    /**
     * @param Company $company
     * @param User    $student
     * @param int     $amount
     * @param Course  $course
     *
     * @return Contract
     * @throws \Exception
     */
    public static function addStudentContract(Company $company, User $student, int $amount, Course $course): Contract
    {
        if ($amount <= 0) throw new \Exception('Сумма договора не может быть отрицательной');
        if ($student->role != User::ROLE_STUDENT) throw new \Exception('Договор может быть создан только для студента');

        $contract = new Contract();
        if (!Yii::$app->user->isGuest && Yii::$app->user->id) {
            $contract->created_admin_id = Yii::$app->user->id;
        }
        $contract->user_id = $student->id;
        $contract->company_id = $company->id;
        $contract->course_id = $course->id;
        $contract->amount = $amount;
        $contract->created_at = date('Y-m-d H:i:s');

        $contract->discount = $course->courseConfig->lesson_price_discount && $amount >= $course->courseConfig->price12Lesson
            ? Contract::STATUS_ACTIVE
            : Contract::STATUS_INACTIVE;
        /** @var CourseStudent $courseStudent */
        $courseStudent = CourseStudent::find()
            ->andWhere(['user_id' => $student->id, 'course_id' => $course->id, 'active' => CourseStudent::STATUS_ACTIVE])
            ->one();
        if ($courseStudent) {
            $courseConfig = CourseComponent::getCourseConfig($courseStudent->course, $courseStudent->startDateObject);
//            if ($courseStudent->startDateObject->format('Y-m') === date('Y-m')) {
//                $courseConfig = CourseComponent::getCourseConfig($courseStudent->course, $courseStudent->startDateObject);
//            } elseif ($courseStudent->startDateObject->format('Y-m') < date('Y-m')) {
//                $groupConfig1 = CourseComponent::getCourseConfig($courseStudent->course, new DateTime('-1 month'));
//                $groupConfig2 = CourseComponent::getCourseConfig($courseStudent->course, new DateTime());
//                $courseConfig = $groupConfig1->price12Lesson < $groupConfig2->price12Lesson ? $groupConfig1 : $groupConfig2;
//            }
            if (isset($courseConfig)) {
                $contract->discount = $courseConfig->lesson_price_discount && $amount >= $courseConfig->price12Lesson
                    ? Contract::STATUS_ACTIVE
                    : Contract::STATUS_INACTIVE;
            }
        }
        if (!$contract->save()) throw new \Exception('Не удалось создать договор: ' . $contract->getErrorsAsString());

        ComponentContainer::getActionLogger()->log(
            Action::TYPE_CONTRACT_ADDED,
            $student,
            $contract->amount,
            $contract->course
        );

        return $contract;
    }

    /**
     * @param Contract      $contract
     * @param DateTime|null $studentStartDate
     * @param int           $payType
     * @param null|string   $paymentComment
     *
     * @return int
     * @throws \Exception
     */
    public static function payContract(Contract $contract, ?DateTimeInterface $studentStartDate, int $payType, ?string $paymentComment = null): int
    {
        if ($contract->status == Contract::STATUS_PAID) throw new \Exception('Договор уже оплачен!');

        $payment = new Payment();
        $payment->admin_id = Yii::$app->user->id;
        $payment->user_id = $contract->user_id;
        $payment->course_id = $contract->course_id;
        $payment->amount = $contract->amount;
        $payment->discount = $contract->discount;
        $payment->contract_id = $contract->id;
        $payment->created_at = date('Y-m-d H:i:s');
        if ($paymentComment) $payment->comment = $paymentComment;

        $contract->status = Contract::STATUS_PAID;
        $contract->payment_type = $payType;
        $contract->paid_admin_id = Yii::$app->user->id;
        $contract->paid_at = date('Y-m-d H:i:s');

        if (!$contract->save()) throw new \Exception('Contract save error: ' . $contract->getErrorsAsString());

        $courseStudent = CourseStudent::find()
            ->andWhere(['user_id' => $contract->user_id, 'course_id' => $contract->course_id, 'active' => CourseStudent::STATUS_ACTIVE])
            ->one();
        if (!$courseStudent && $studentStartDate) {
            CourseComponent::addStudentToCourse($contract->user, $contract->course, $studentStartDate);
        }

        $paymentId = self::registerIncome($payment);
        $contract->link('payments', $payment);
        ComponentContainer::getActionLogger()->log(
            Action::TYPE_CONTRACT_PAID,
            $contract->user,
            $contract->amount,
            $contract->course
        );
        self::setUserChargeDates($contract->user, $contract->course);

        return $paymentId;
    }

    public static function recalculateDebt(User $user, Course $course): bool
    {
        $balance = Payment::find()
            ->andWhere(['user_id' => $user->id, 'course_id' => $course->id])
            ->select('SUM(amount)')
            ->scalar();
        $newDebt = $balance * (-1);

        $debt = Debt::findOne(['user_id' => $user->id, 'course_id' => $course->id]);
        if ($debt) {
            if ($newDebt <= 0) return boolval($debt->delete());
            elseif ($newDebt != $debt->amount) {
                $debt->amount = $newDebt;
                return $debt->save();
            }
        } elseif ($newDebt > 0) {
            $debt = new Debt();
            $debt->user_id = $user->id;
            $debt->course_id = $course->id;
            $debt->amount = $newDebt;
            return $debt->save();
        }
        return true;
    }

    public static function chargeByEvent(Event $event): void
    {
        switch ($event->status) {
            case Event::STATUS_UNKNOWN:
                return;
            case Event::STATUS_PASSED:
                $paymentStub = new Payment();
                $paymentStub->admin_id = Yii::$app->user->id;
                $paymentStub->course_id = $event->course_id;
                $paymentStub->created_at = $event->event_date;

                foreach ($event->membersWithPayments as $eventMember) {
                    if (!$eventMember->payments) {
                        $rate = '1';
                        $courseConfig = CourseComponent::getCourseConfig($event->course, $event->eventDateTime);

                        while (bccomp($rate, '0', 8) > 0) {
                            $payment = clone $paymentStub;
                            $payment->user_id = $eventMember->courseStudent->user_id;
                            $payment->event_member_id = $eventMember->id;

                            /** @var Payment $parentPayment */
                            $parentPayment = Payment::find()
                                ->alias('p')
                                ->andWhere(['p.user_id' => $eventMember->courseStudent->user_id, 'p.course_id' => $event->course_id])
                                ->andWhere(['>', 'p.amount', 0])
                                ->joinWith('payments ch')
                                ->select(['p.id', 'p.amount', 'p.discount', 'SUM(ch.amount) as spent'])
                                ->groupBy(['p.id', 'p.amount', 'p.discount'])
                                ->orderBy(['p.created_at' => SORT_ASC])
                                ->having('spent IS NULL OR p.amount > (spent * -1)')
                                ->one();

                            if ($parentPayment) {
                                $isDiscount = $courseConfig->lesson_price_discount && $parentPayment->discount;
                                $lessonPrice = $isDiscount ? $courseConfig->lesson_price_discount : $courseConfig->lesson_price;
                                $toPay = MoneyHelper::roundTen((int) bcmul($rate, (string) $lessonPrice, 0));

                                if ($parentPayment->moneyLeft >= $toPay) {
                                    $payment->amount = $toPay * (-1);
                                    $payment->discount = $isDiscount ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;
                                    $rate = '0';
                                } else {
                                    $payment->amount = $parentPayment->moneyLeft * (-1);
                                    $payment->discount = $isDiscount ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;
                                    $rate = bcadd($rate, number_format($payment->amount / $lessonPrice, 8, '.', ''));
                                }
                                $payment->used_payment_id = $parentPayment->id;
                            } else {
                                $toPay = MoneyHelper::roundTen((int) bcmul($rate, (string) $courseConfig->lesson_price, 0));
                                $payment->amount = $toPay * (-1);
                                $payment->discount = Payment::STATUS_INACTIVE;
                                $rate = '0';
                            }
                            self::savePayment($payment);
                            $eventMember->link('payments', $payment);
                        }
                    }
                }
                break;
            case Event::STATUS_CANCELED:
                foreach ($event->members as $eventMember) {
                    foreach ($eventMember->payments as $payment) {
                        self::cancelPayment($payment);
                    }
                }
                break;
        }
    }


    /**
     * @param Payment $payment
     * @param int $newAmount
     * @throws \Exception
     */
    public static function decreasePayment(Payment $payment, int $newAmount): void
    {
        if ($payment->amount <= 0) throw new \Exception('Wrong payment! You are not able to decrease negative payments: ' . $payment->id);
        $diff = $newAmount - $payment->amount;
        $payment->amount = $newAmount;
        if (!$payment->save()) throw new \Exception('Error decreasing payment: ' . $payment->getErrorsAsString());
        self::addStudentMoney($payment->user, $diff, $payment->course);
    }

    /**
     * @param Payment $payment
     * @param bool $logEvent
     * @throws \Exception
     * @throws \Throwable
     */
    public static function cancelPayment(Payment $payment, bool $logEvent = true)
    {
        if ($payment->amount > 0) throw new \Exception('Wrong payment! You are not able to cancel positive payments: ' . $payment->id);
        if (!$payment->delete()) throw new \Exception('Error deleting payment from DB: ' . $payment->id);
        self::addStudentMoney($payment->user, $payment->amount * (-1), $payment->course);
        $paymentComment = 'Отмена списания за ' . $payment->createDate->format('d F Y') . ' в группе "' . $payment->courseConfig->name . '"';
        if ($logEvent && !ComponentContainer::getActionLogger()->log(
            Action::TYPE_CANCEL_AUTO,
            $payment->user,
            $payment->amount * (-1),
            $payment->course,
            $paymentComment
        )) throw new \Exception('Error logging payment cancellation: ' . $payment->id);
    }

    /**
     * @param Payment $payment
     * @param int $actionType
     * @param bool $logEvent
     * @throws \Exception
     */
    public static function savePayment(Payment $payment, $actionType = Action::TYPE_CHARGE, bool $logEvent = true)
    {
        if (!$payment->save()) throw new \Exception('Error adding payment to DB: ' . $payment->getErrorsAsString());
        self::addStudentMoney($payment->user, $payment->amount, $payment->course);
        $paymentComment = 'Списание за ' . $payment->createDate->format('d F Y') . ' в группе "' . $payment->courseConfig->name . '"';
        if ($logEvent && !ComponentContainer::getActionLogger()->log(
            $actionType,
            $payment->user,
            $payment->amount,
            $payment->course,
            $payment->comment ?: ($actionType == Action::TYPE_INCOME ? null : $paymentComment)
        )) throw new \Exception('Error logging payment: ' . $payment->id);
    }

    public static function setUserChargeDates(User $student, Course $course)
    {
        /** @var CourseStudent[] $courseStudents */
        $courseStudents = CourseStudent::find()->andWhere(['user_id' => $student->id, 'course_id' => $course->id])->all();
        if (count($courseStudents) === 0) {
            return;
        }

        /*    Собираем информацию обо всех внесенных средствах    */
        $money = (int) Payment::find()
            ->andWhere(['user_id' => $student->id, 'course_id' => $course->id, 'discount' => Payment::STATUS_INACTIVE])
            ->andWhere(['event_member_id' => null])
            ->select('SUM(amount)')->scalar();
        $moneyDiscount = (int) Payment::find()
            ->andWhere(['user_id' => $student->id, 'course_id' => $course->id, 'discount' => Payment::STATUS_ACTIVE])
            ->andWhere(['event_member_id' => null])
            ->select('SUM(amount)')->scalar();
        /** @var array<int,{entity:CourseStudent,state:bool}> $courseStudentMap */
        $courseStudentMap = [];
        foreach ($courseStudents as $courseStudent) {
            $courseStudent->paid_lessons = 0;
            $courseStudentMap[$courseStudent->id] = ['entity' => $courseStudent, 'state' => false];
        }
        $courseStudentIds = array_keys($courseStudentMap);

        /*    Двигаемся по всем группам и снимаем с остатков средства за занятия   */
        /** @var Event[] $events */
        $events = Event::find()
            ->joinWith('members')
            ->with([
                'members' => function (ActiveQuery $query) use ($courseStudentIds) {
                    $query->andWhere(['course_student_id' => $courseStudentIds])
                        ->with('payments');
                },
            ])
            ->andWhere([
                Event::tableName() . '.course_id' => $course->id,
                Event::tableName() . '.status' => Event::STATUS_PASSED,
                EventMember::tableName() . '.course_student_id' => $courseStudentIds,
            ])
            ->orderBy('event_date')
            ->all();

        foreach ($events as $event) {
            foreach ($event->members as $member) {
                foreach ($member->payments as $payment) {
                    $courseConfig = CourseComponent::getCourseConfig($course, $event->eventDateTime);
                    if ($moneyDiscount > 0 && ($payment->discount || !$courseConfig->lesson_price_discount)) {
                        $moneyDiscount += $payment->amount;
                    } else {
                        $money += $payment->amount;
                    }
                }
                if ($moneyDiscount <= 0 && $money < 0) {
                    if (!$courseStudentMap[$member->course_student_id]['state']) {
                        $courseStudentMap[$member->course_student_id]['entity']->date_charge_till = $event->event_date;
                        $courseStudentMap[$member->course_student_id]['state'] = true;
                    }
                    $courseStudentMap[$member->course_student_id]['entity']->paid_lessons--;
                }
            }
        }

        /* Проверяем всем ли уже проставили даты */
        $continue = 0;
        $nowDate = new DateTimeImmutable('midnight');
        foreach ($courseStudentMap as $item) {
            if (!$item['state']) {

                $continue++;
                if ((!$item['entity']->date_end || $item['entity']->endDateObject >= $nowDate)
                    && (!$item['entity']->course->date_end || $item['entity']->course->endDateObject >= $nowDate)) {
                    $item['entity']->paid_lessons = 0;
                }
            }
        }

        /* Если не всем, то двигаемся в будущее */
        $currentDate = new DateTime('midnight');
        while ($continue > 0) {
            $w = intval($currentDate->format('w'));
            foreach ($courseStudentMap as $id => $item) {
                if (!$item['state']) {
                    if ($item['entity']->date_end && $item['entity']->endDateObject <= $currentDate) {
                        $item['entity']->date_charge_till = $item['entity']->date_end;
                        $courseStudentMap[$id]['state'] = true;
                        $continue--;
                    } elseif ($item['entity']->course->date_end && $item['entity']->course->endDateObject <= $currentDate) {
                        $item['entity']->date_charge_till = $item['entity']->course->date_end;
                        $courseStudentMap[$id]['state'] = true;
                        $continue--;
                    } else {
                        /** @var CourseConfig $config */
                        $config = $item['entity']->course->startDateObject > $nowDate
                            ? $item['entity']->course->courseConfigs[0]
                            : $item['entity']->course->getCourseConfigByDate($currentDate);
                        if (!empty($config->schedule[($w + 6) % 7])) {
                            $toCharge = $config->lesson_price;
                            if ($moneyDiscount > 0) {
                                if ($config->lesson_price_discount) {
                                    $toCharge = $config->lesson_price_discount;
                                }
                                if ($moneyDiscount > $toCharge) {
                                    $moneyDiscount -= $toCharge;
                                    $item['entity']->paid_lessons++;
                                    continue;
                                } else {
                                    $toCharge = (int) round($config->lesson_price * (1 - ($moneyDiscount / $toCharge)));
                                    $moneyDiscount = 0;
                                }
                            }

                            $money -= $toCharge;
                            if ($money >= 0) {
                                $item['entity']->paid_lessons++;
                            }
                            if ($money <= 0) {
                                $item['entity']->date_charge_till = $currentDate->format('Y-m-d H:i:s');
                                $courseStudentMap[$id]['state'] = true;
                                $continue--;
                            }
                        }
                    }
                }
            }
            $currentDate->modify('+1 day');

            if (intval($currentDate->format('Y')) > intval($nowDate->format('Y')) + 1) {
                foreach ($courseStudentMap as $item) {
                    if (!$item['state']) {
                        $item['entity']->date_charge_till = $currentDate->format('Y-m-d H:i:s');
                    }
                }
                break;
            } // Когда внесли миллиарды оплаты
        }

        foreach ($courseStudentMap as $item) {
            $item['entity']->save();
        }
    }

    public static function rechargeStudent(User $student, Course $course)
    {
        /** @var CourseStudent[] $courseStudents */
        $courseStudents = CourseStudent::find()
            ->andWhere(['user_id' => $student->id, 'course_id' => $course->id])
            ->orderBy(['active' => SORT_ASC, 'date_start' => SORT_ASC])
            ->with(['eventMembers' => function (ActiveQuery $query) {
                    $query
                        ->joinWith('event')
                        ->andWhere([Event::tableName() . '.status' => Event::STATUS_PASSED])
                        ->with(['event', 'payments']);
                }])
            ->all();
        if (!$courseStudents) return;

        /** @var array $payments */
        $payments = Payment::find()
            ->andWhere(['user_id' => $student->id, 'course_id' => $course->id])
            ->andWhere(['>', 'amount', 0])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->select(['id', 'amount', 'discount'])
            ->asArray()
            ->all();

        /** @var CourseConfig[] $courseConfigs */
        $courseConfigs = CourseConfig::find()->andWhere(['course_id' => $course->id])->orderBy(['date_from' => SORT_ASC])->all();

        $sortPayments = function($a, $b) {
            if ($a['id'] > $b['id']) return -1;
            if ($a['id'] < $b['id']) return 1;
            return $a['amount'] - $b['amount'];
        };

        foreach ($courseStudents as $courseStudent) {
            $key = 0;
            foreach ($courseStudent->eventMembers as $eventMember) {
                while (count($courseConfigs) > $key + 1 && $courseConfigs[$key + 1]->date_from <= $eventMember->event->eventDateTime->format('Y-m-d')) {
                    ++$key;
                }
                $rate = '1';
                $toCharge = [];
                while (bccomp($rate, '0', 8) > 0) {
                    if (!empty($payments)) {
                        $isDiscount = $payments[0]['discount'] && $courseConfigs[$key]->lesson_price_discount;
                        $lessonPrice = $isDiscount ? $courseConfigs[$key]->lesson_price_discount : $courseConfigs[$key]->lesson_price;

                        $toPay = MoneyHelper::roundTen((int) bcmul($rate, (string) $lessonPrice, 0));
                        $toChargeItem = ['id' => $payments[0]['id'], 'discount' => $isDiscount ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE];
                        if ($payments[0]['amount'] >= $toPay) {
                            $toChargeItem['amount'] = $toPay;
                            $payments[0]['amount'] -= $toPay;
                            $rate = '0';
                        } else {
                            $toChargeItem['amount'] = $payments[0]['amount'];
                            $rate = bcsub($rate, number_format($payments[0]['amount'] / $lessonPrice, 8, '.', ''), 8);
                            $payments[0]['amount'] = 0;
                        }
                        $toCharge[] = $toChargeItem;

                        if ($payments[0]['amount'] <= 0) array_shift($payments);
                    } else {
                        $toCharge[] = ['id' => null, 'discount' => 0, 'amount' => (int) bcmul($rate, (string) $courseConfigs[$key]->lesson_price, 0)];
                        $rate = '0';
                    }
                }

                usort($toCharge, $sortPayments);
                $charged = [];
                foreach ($eventMember->payments as $payment) {
                    $charged[] = ['id' => $payment->used_payment_id, 'discount' => $payment->discount, 'amount' => $payment->amount * (-1)];
                }
                usort($charged, $sortPayments);
                if ($toCharge !== $charged) {
                    foreach ($eventMember->payments as $payment) {
                        self::cancelPayment($payment);
                    }
                    foreach ($toCharge as $paymentData) {
                        $payment = new Payment();
                        $payment->user_id = $student->id;
                        $payment->admin_id = Yii::$app->user->id;
                        $payment->course_id = $course->id;
                        $payment->event_member_id = $eventMember->id;
                        $payment->created_at = $eventMember->event->event_date;
                        $payment->used_payment_id = $paymentData['id'];
                        $payment->discount = $paymentData['discount'];
                        $payment->amount = $paymentData['amount'] * (-1);
                        self::savePayment($payment);
                    }
                }
            }
        }
    }
}
