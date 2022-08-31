<?php

namespace common\components;

use common\models\Company;
use common\models\Contract;
use common\models\Debt;
use backend\models\Event;
use backend\models\EventMember;
use common\models\Course;
use common\models\CourseConfig;
use common\models\GroupParam;
use common\models\CourseStudent;
use common\models\Payment;
use common\models\User;
use DateTime;
use Yii;
use yii\base\Component;
use yii\db\ActiveQuery;

class MoneyComponent extends Component
{
    /**
     * @param User   $pupil
     * @param int    $amount
     * @param Course $group
     *
     * @throws \Exception
     */
    protected static function addPupilMoney(User $pupil, int $amount, Course $group)
    {
        if ($pupil->role != User::ROLE_STUDENT) throw new \Exception('Money can be changed only for pupils');

        $pupil->money += $amount;
        if (!$pupil->save(true, ['money'])) throw new \Exception($pupil->getErrorsAsString());
        if (!self::recalculateDebt($pupil, $group)) throw new \Exception('Error on pupil\'s debt calculation');
    }

    /**
     * @param Payment $payment
     * @return int
     * @throws \Throwable
     */
    public static function registerIncome(Payment $payment)
    {
        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            self::savePayment($payment, Action::TYPE_INCOME, !empty(Yii::$app->user->id));
            self::rechargePupil($payment->user, $payment->group);
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
     * @param User    $pupil
     * @param int     $amount
     * @param Course  $group
     *
     * @return Contract
     * @throws \Exception
     */
    public static function addPupilContract(Company $company, User $pupil, int $amount, Course $group): Contract
    {
        if ($amount <= 0) throw new \Exception('Сумма договора не может быть отрицательной');
        if ($pupil->role != User::ROLE_STUDENT) throw new \Exception('Договор может быть создан только для студента');

        $contract = new Contract();
        if (!Yii::$app->user->isGuest && Yii::$app->user->id) {
            $contract->created_admin_id = Yii::$app->user->id;
        }
        $contract->user_id = $pupil->id;
        $contract->company_id = $company->id;
        $contract->course_id = $group->id;
        $contract->amount = $amount;
        $contract->created_at = date('Y-m-d H:i:s');

        $contract->discount = $group->lesson_price_discount && $amount >= $group->price12Lesson
            ? Contract::STATUS_ACTIVE
            : Contract::STATUS_INACTIVE;
        /** @var CourseStudent $groupPupil */
        $groupPupil = CourseStudent::find()
            ->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id, 'active' => CourseStudent::STATUS_ACTIVE])
            ->one();
        if ($groupPupil) {
            if ($groupPupil->startDateObject->format('Y-m') === date('Y-m')) {
                $groupConfig = CourseComponent::getCourseConfig($groupPupil->group, $groupPupil->startDateObject);
            } elseif ($groupPupil->startDateObject->format('Y-m') < date('Y-m')) {
                $groupConfig1 = CourseComponent::getCourseConfig($groupPupil->group, new DateTime('-1 month'));
                $groupConfig2 = CourseComponent::getCourseConfig($groupPupil->group, new DateTime());
                $groupConfig = $groupConfig1->price12Lesson < $groupConfig2->price12Lesson ? $groupConfig1 : $groupConfig2;
            }
            if (isset($groupConfig)) {
                $contract->discount = $groupConfig->lesson_price_discount && $amount >= $groupConfig->price12Lesson
                    ? Contract::STATUS_ACTIVE
                    : Contract::STATUS_INACTIVE;
            }
        }
        if (!$contract->save()) throw new \Exception('Не удалось создать договор: ' . $contract->getErrorsAsString());

        ComponentContainer::getActionLogger()->log(
            Action::TYPE_CONTRACT_ADDED,
            $pupil,
            $contract->amount,
            $contract->course
        );

        return $contract;
    }

    /**
     * @param Contract $contract
     * @param DateTime|null $pupilStartDate
     * @param int $payType
     * @param null|string $paymentComment
     * @return int
     * @throws \Exception
     */
    public static function payContract(Contract $contract, ?DateTime $pupilStartDate, int $payType, ?string $paymentComment = null): int
    {
        if ($contract->status == Contract::STATUS_PAID) throw new \Exception('Договор уже оплачен!');

        $payment = new Payment();
        $payment->admin_id = Yii::$app->user->id;
        $payment->user_id = $contract->user_id;
        $payment->group_id = $contract->course_id;
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

        $groupPupil = CourseStudent::find()
            ->andWhere(['user_id' => $contract->user_id, 'group_id' => $contract->course_id, 'active' => CourseStudent::STATUS_ACTIVE])
            ->one();
        if (!$groupPupil && $pupilStartDate) {
            CourseComponent::addPupilToGroup($contract->user, $contract->course, $pupilStartDate);
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
        CourseComponent::calculateTeacherSalary($contract->course);

        return $paymentId;
    }

    /**
     * @param User   $user
     * @param Course $group
     *
     * @return bool
     */
    public static function recalculateDebt(User $user, Course $group): bool
    {
        $balance = Payment::find()
            ->andWhere(['user_id' => $user->id, 'group_id' => $group->id])
            ->select('SUM(amount)')
            ->scalar();
        $newDebt = $balance * (-1);

        $debt = Debt::findOne(['user_id' => $user->id, 'group_id' => $group->id]);
        if ($debt) {
            if ($newDebt <= 0) return boolval($debt->delete());
            elseif ($newDebt != $debt->amount) {
                $debt->amount = $newDebt;
                return $debt->save();
            }
        } elseif ($newDebt > 0) {
            $debt = new Debt();
            $debt->user_id = $user->id;
            $debt->course_id = $group->id;
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
                $paymentStub->group_id = $event->course_id;
                $paymentStub->created_at = $event->event_date;

                foreach ($event->membersWithPayments as $eventMember) {
                    if (!$eventMember->payments) {
                        $rate = '1';
                        $groupConfig = CourseComponent::getCourseConfig($event->group, $event->eventDateTime);

                        while (bccomp($rate, '0', 8) > 0) {
                            $payment = clone $paymentStub;
                            $payment->user_id = $eventMember->groupPupil->user_id;
                            $payment->event_member_id = $eventMember->id;

                            /** @var Payment $parentPayment */
                            $parentPayment = Payment::find()
                                ->alias('p')
                                ->andWhere(['p.user_id' => $eventMember->groupPupil->user_id, 'p.group_id' => $event->course_id])
                                ->andWhere(['>', 'p.amount', 0])
                                ->joinWith('payments ch')
                                ->select(['p.id', 'p.amount', 'p.discount', 'SUM(ch.amount) as spent'])
                                ->groupBy(['p.id', 'p.amount', 'p.discount'])
                                ->orderBy(['p.created_at' => SORT_ASC])
                                ->having('spent IS NULL OR p.amount > (spent * -1)')
                                ->one();

                            if ($parentPayment) {
                                $isDiscount = $groupConfig->lesson_price_discount && $parentPayment->discount;
                                $lessonPrice = $isDiscount ? $groupConfig->lesson_price_discount : $groupConfig->lesson_price;
                                $toPay = (int) bcmul($rate, (string) $lessonPrice, 0);

                                if ($parentPayment->moneyLeft >= $toPay) {
                                    $payment->amount = $toPay * (-1);
                                    $payment->discount = $isDiscount ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;
                                    $rate = '0';
                                } else {
                                    $payment->amount = $parentPayment->moneyLeft * (-1);
                                    $payment->discount = $isDiscount ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;
                                    $rate = bcsub($rate, number_format($payment->amount * (-1) / $lessonPrice, 8, '.', ''));
                                }
                                $payment->used_payment_id = $parentPayment->id;
                            } else {
                                $toPay = (int) bcmul($rate, (string) $groupConfig->lesson_price, 0);
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
    public static function decreasePayment(Payment $payment, int $newAmount)
    {
        if ($payment->amount <= 0) throw new \Exception('Wrong payment! You are not able to decrease negative payments: ' . $payment->id);
        $diff = $newAmount - $payment->amount;
        $payment->amount = $newAmount;
        if (!$payment->save()) throw new \Exception('Error decreasing payment: ' . $payment->getErrorsAsString());
        self::addPupilMoney($payment->user, $diff, $payment->group);
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
        self::addPupilMoney($payment->user, $payment->amount * (-1), $payment->group);
        $paymentComment = 'Отмена списания за ' . $payment->createDate->format('d F Y') . ' в группе "' . $payment->group->name . '"';
        if ($logEvent && !ComponentContainer::getActionLogger()->log(
            Action::TYPE_CANCEL_AUTO,
            $payment->user,
            $payment->amount * (-1),
            $payment->group,
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
        self::addPupilMoney($payment->user, $payment->amount, $payment->group);
        $paymentComment = 'Списание за ' . $payment->createDate->format('d F Y') . ' в группе "' . $payment->group->name . '"';
        if ($logEvent && !ComponentContainer::getActionLogger()->log(
            $actionType,
            $payment->user,
            $payment->amount,
            $payment->group,
            $payment->comment ?: ($actionType == Action::TYPE_INCOME ? null : $paymentComment)
        )) throw new \Exception('Error logging payment: ' . $payment->id);
    }

    public static function setUserChargeDates(User $user, Course $group)
    {
        /** @var CourseStudent[] $groupPupils */
        $groupPupils = CourseStudent::find()->andWhere(['user_id' => $user->id, 'group_id' => $group->id])->all();
        if (count($groupPupils) === 0) return;

        /*    Собираем информацию обо всех внесенных средствах    */
        $money = (int) Payment::find()
            ->andWhere(['user_id' => $user->id, 'group_id' => $group->id, 'discount' => Payment::STATUS_INACTIVE])
            ->andWhere(['event_member_id' => null])
            ->select('SUM(amount)')->scalar();
        $moneyDiscount = (int) Payment::find()
            ->andWhere(['user_id' => $user->id, 'group_id' => $group->id, 'discount' => Payment::STATUS_ACTIVE])
            ->andWhere(['event_member_id' => null])
            ->select('SUM(amount)')->scalar();
        $groupPupilMap = [];
        foreach ($groupPupils as $groupPupil) {
            $groupPupil->paid_lessons = 0;
            $groupPupilMap[$groupPupil->id] = ['entity' => $groupPupil, 'state' => false];
        }
        $groupPupilIds = array_keys($groupPupilMap);

        /*    Двигаемся по всем группам и снимаем с остатков средства за занятия   */
        /** @var Event[] $events */
        $events = Event::find()
            ->joinWith('members')
            ->with([
                'members' => function (ActiveQuery $query) use ($groupPupilIds) {
                    $query->andWhere(['group_pupil_id' => $groupPupilIds])
                        ->with('payments');
                },
            ])
            ->andWhere([
                Event::tableName() . '.group_id' => $group->id,
                Event::tableName() . '.status' => Event::STATUS_PASSED,
                EventMember::tableName() . '.group_pupil_id' => $groupPupilIds,
            ])
            ->orderBy('event_date')
            ->all();

        foreach ($events as $event) {
            foreach ($event->members as $member) {
                foreach ($member->payments as $payment) {
                    $groupConfig = CourseComponent::getCourseConfig($group, $event->eventDateTime);
                    if ($moneyDiscount > 0 && ($payment->discount || !$groupConfig->lesson_price_discount)) {
                        $moneyDiscount += $payment->amount;
                    } else {
                        $money += $payment->amount;
                    }
                }
                if ($moneyDiscount <= 0 && $money < 0) {
                    if (!$groupPupilMap[$member->course_student_id]['state']) {
                        $groupPupilMap[$member->course_student_id]['entity']->date_charge_till = $event->event_date;
                        $groupPupilMap[$member->course_student_id]['state'] = true;
                    }
                    $groupPupilMap[$member->course_student_id]['entity']->paid_lessons--;
                }
            }
        }

        /* Проверяем всем ли уже проставили даты */
        $continue = 0;
        foreach ($groupPupilMap as $item) {
            if (!$item['state']) $continue++;
        }

        /* Если не всем, то двигаемся в будущее */
        if ($continue > 0) {
            $nowDate = new DateTime('midnight');

            foreach ($groupPupilMap as $id => $item) {
                if (!$item['state'] && (!$item['entity']->date_end || $item['entity']->endDateObject >= $nowDate)
                    && (!$item['entity']->group->date_end || $item['entity']->group->endDateObject >= $nowDate)) {
                    $item['entity']->paid_lessons = 0;
                    $groupPupilMap[$id]['config'] = CourseComponent::getCourseConfig($item['entity']->group, $nowDate);
                }
            }

            $currentDate = new DateTime('midnight');
            $checkParam = true;
            while ($continue > 0) {
                if ($checkParam && $nowDate->format('Y-m') != $currentDate->format('Y-m')) {
                    foreach ($groupPupilMap as $id => $item) {
                        if (!$item['state']) {
                            $newParam = new GroupParam();
                            $newParam->lesson_price = $item['entity']->group->lesson_price;
                            $newParam->lesson_price_discount = $item['entity']->group->lesson_price_discount;
                            $newParam->schedule = $item['entity']->group->schedule;
                            $groupPupilMap[$id]['param'] = $newParam;
                        }
                    }
                    $checkParam = false;
                }

                $w = intval($currentDate->format('w'));
                foreach ($groupPupilMap as $id => $item) {
                    if (!$item['state']) {
                        if ($item['entity']->date_end && $item['entity']->endDateObject <= $currentDate) {
                            $item['entity']->date_charge_till = $item['entity']->date_end;
                            $groupPupilMap[$id]['state'] = true;
                            $continue--;
                        } elseif ($item['entity']->group->date_end && $item['entity']->group->endDateObject <= $currentDate) {
                            $item['entity']->date_charge_till = $item['entity']->group->date_end;
                            $groupPupilMap[$id]['state'] = true;
                            $continue--;
                        } elseif (!empty($item['param']->scheduleData[($w + 6) % 7])) {
                            $toCharge = $item['param']->lesson_price;
                            if ($moneyDiscount > 0) {
                                if ($item['param']->lesson_price_discount) {
                                    $toCharge = $item['param']->lesson_price_discount;
                                }
                                if ($moneyDiscount > $toCharge) {
                                    $moneyDiscount -= $toCharge;
                                    $item['entity']->paid_lessons++;
                                    continue;
                                } else {
                                    $toCharge = (int) round($item['param']->lesson_price * (1 - ($moneyDiscount / $toCharge)));
                                    $moneyDiscount = 0;
                                }
                            }

                            $money -= $toCharge;
                            if ($money >= 0) {
                                $item['entity']->paid_lessons++;
                            }
                            if ($money <= 0) {
                                $item['entity']->date_charge_till = $currentDate->format('Y-m-d H:i:s');
                                $groupPupilMap[$id]['state'] = true;
                                $continue--;
                            }
                        }
                    }
                }
                $currentDate->modify('+1 day');

                if (intval($currentDate->format('Y')) > intval($nowDate->format('Y')) + 1) {
                    foreach ($groupPupilMap as $id => $item) {
                        if (!$item['state']) $item['entity']->date_charge_till = $currentDate->format('Y-m-d H:i:s');
                    }
                    break;
                } // Когда внесли миллиарды оплаты
            }
        }

        foreach ($groupPupilMap as $id => $item) {
            $item['entity']->save();
        }
    }

    public static function rechargePupil(User $pupil, Course $group)
    {
        /** @var CourseStudent[] $groupPupils */
        $groupPupils = CourseStudent::find()
            ->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id])
            ->orderBy(['active' => SORT_ASC, 'date_start' => SORT_ASC])
            ->with(['eventMembers' => function (ActiveQuery $query) {
                    $query
                        ->joinWith('event')
                        ->andWhere([Event::tableName() . '.status' => Event::STATUS_PASSED])
                        ->with(['event', 'payments']);
                }])
            ->all();
        if (!$groupPupils) return;

        /** @var array $payments */
        $payments = Payment::find()
            ->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id])
            ->andWhere(['>', 'amount', 0])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->select(['id', 'amount', 'discount'])
            ->asArray()
            ->all();

        /** @var CourseConfig[] $groupConfigs */
        $groupConfigs = CourseConfig::find()->andWhere(['group_id' => $group->id])->orderBy(['date_from' => SORT_ASC])->all();

        $sortPayments = function($a, $b) {
            if ($a['id'] > $b['id']) return -1;
            if ($a['id'] < $b['id']) return 1;
            return $a['amount'] - $b['amount'];
        };

        foreach ($groupPupils as $groupPupil) {
            $key = 0;
            foreach ($groupPupil->eventMembers as $eventMember) {
                while (count($groupConfigs) > $key + 1 && $groupConfigs[$key + 1]->date_from <= $eventMember->event->eventDateTime->format('Y-m-d')) {
                    ++$key;
                }
                $rate = '1';
                $toCharge = [];
                while (bccomp($rate, '0', 8) > 0) {
                    if (!empty($payments)) {
                        $isDiscount = $payments[0]['discount'] && $groupConfigs[$key]->lesson_price_discount;
                        $lessonPrice = $isDiscount ? $groupConfigs[$key]->lesson_price_discount : $groupConfigs[$key]->lesson_price;

                        $toPay = (int) bcmul($rate, (string) $lessonPrice, 0);
                        $toChargeItem = ['id' => $payments[0]['id'], 'discount' => $isDiscount ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE];
                        if ($payments[0]['amount'] >= $toPay) {
                            $toChargeItem['amount'] = $toPay;
                            $payments[0]['amount'] -= $toPay;
                            $rate = '0';
                        } else {
                            $toChargeItem['amount'] = $payments[0]['amount'];
                            $rate = bcsub($rate, number_format($payments[0]['amount'] * (-1) / $lessonPrice, 8, '.', ''));
                            $payments[0]['amount'] = 0;
                        }
                        $toCharge[] = $toChargeItem;

                        if ($payments[0]['amount'] <= 0) array_shift($payments);
                    } else {
                        $toCharge[] = ['id' => null, 'discount' => 0, 'amount' => (int) bcmul($rate, (string) $groupConfigs[$key]->lesson_price, 0)];
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
                        $payment->user_id = $pupil->id;
                        $payment->admin_id = Yii::$app->user->id;
                        $payment->group_id = $group->id;
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
