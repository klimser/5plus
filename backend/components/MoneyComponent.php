<?php

namespace backend\components;

use backend\models\Debt;
use backend\models\Event;
use backend\models\EventMember;
use backend\models\Group;
use backend\models\GroupParam;
use backend\models\GroupPupil;
use backend\models\Payment;
use backend\models\User;
use common\components\Action;
use yii\base\Component;
use yii\db\ActiveQuery;

class MoneyComponent extends Component
{
    /**
     * @param User $pupil
     * @param int $amount
     * @throws \Exception
     */
    protected static function addPupilMoney(User $pupil, int $amount)
    {
        if ($pupil->role != User::ROLE_PUPIL) throw new \Exception('Money can be changed only for pupils');

        $pupil->money += $amount;
        if (!$pupil->save(true, ['money'])) throw new \Exception($pupil->getErrorsAsString());
        if (!self::recalculateDebt($pupil)) throw new \Exception('Error on pupil\'s debt calculation');
    }

    /**
     * @param Payment $payment
     * @return int
     * @throws \Throwable
     */
    public static function registerIncome(Payment $payment)
    {
        $transaction = \Yii::$app->getDb()->beginTransaction();
        try {
            self::savePayment($payment, Action::TYPE_INCOME);
            if ($payment->group_pupil_id) {
                GroupComponent::rechargeGroupPupil($payment->groupPupil);
                if (!self::recalculateDebt($payment->user)) throw new \Exception('Error on pupil\'s debt calculation');
            }

            $transaction->commit();
            return $payment->id;
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            \Yii::$app->errorLogger->logError('money/income', $ex->getMessage(), true);
            throw $ex;
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    public static function recalculateDebt(User $user): bool
    {
        $newDebt = $user->getBalance(true) * (-1);

        $debt = Debt::findOne(['user_id' => $user->id]);
        if ($debt) {
            if ($newDebt <= 0) return boolval($debt->delete());
            elseif ($newDebt != $debt->amount) {
                $debt->amount = $newDebt;
                return $debt->save();
            }
        } elseif ($newDebt > 0) {
            $debt = new Debt();
            $debt->user_id = $user->id;
            $debt->amount = $newDebt;
            return $debt->save();
        }
        return true;
    }

    /**
     * @param GroupPupil $groupPupil
     * @param \DateTime $paymentDate
     * @param int $amount
     * @return array
     */
    public static function findDiscountPayments(GroupPupil $groupPupil, \DateTime $paymentDate, int $amount): array
    {
        /** @var Payment[] $incomePayments */
        $incomePayments = Payment::find()
            ->andWhere(['group_pupil_id' => $groupPupil->id])
            ->andWhere(['>', 'amount', 0])
            ->andWhere(['<=', 'created_at', $paymentDate->format('Y-m-d') . ' 23:59:59'])
            ->orderBy(['created_at' => SORT_ASC])->all();
        $output = [];
        foreach ($incomePayments as $incomePayment) {
            $outcomeSum = Payment::find()
                ->andWhere(['used_payment_id' => $incomePayment->id])
                ->select('SUM(amount)')->scalar();
            if ($outcomeSum * (-1) < $incomePayment->amount) {
                $output[$incomePayment->id] = min($incomePayment->amount, $amount);
                if ($incomePayment->amount >= $amount) break;
                $amount -= $incomePayment->amount;
            }
        }
        return $output;
    }

    /**
     * @param Event $event
     * @throws \Exception
     */
    public static function chargeByEvent(Event $event)
    {
        if ($event->status == Event::STATUS_UNKNOWN) return;

        if ($event->status == Event::STATUS_PASSED) {
            $paymentStub = new Payment();
            $paymentStub->admin_id = \Yii::$app->user->id;
            $paymentStub->created_at = $event->event_date;

            foreach ($event->members as $eventMember) {
                if (!$eventMember->payments) {
                    $groupParam = GroupComponent::getGroupParam($event->group, $event->eventDateTime);
                    if ($groupParam->lesson_price_discount) {
                        $chargeAmount = $groupParam->lesson_price_discount;

                        $discountPayments = self::findDiscountPayments($eventMember->groupPupil, $event->eventDateTime, $chargeAmount);
                        if ($discountPayments) {
                            foreach ($discountPayments as $paymentId => $paymentSum) {
                                $payment = clone $paymentStub;
                                $payment->user_id = $eventMember->groupPupil->user_id;
                                $payment->group_pupil_id = $eventMember->group_pupil_id;
                                $payment->event_member_id = $eventMember->id;
                                $payment->used_payment_id = $paymentId;
                                $payment->amount = $paymentSum * (-1);

                                self::savePayment($payment);
                                $chargeAmount -= $paymentSum;
                            }
                        }
                    } else $chargeAmount = $groupParam->lesson_price;
                    if ($chargeAmount > 0) {
                        if ($groupParam->lesson_price_discount) {
                            $toCharge = intval(round(($chargeAmount / $groupParam->lesson_price_discount) * $groupParam->lesson_price));
                        } else $toCharge = $chargeAmount;
                        $payment = clone $paymentStub;
                        $payment->user_id = $eventMember->groupPupil->user_id;
                        $payment->group_pupil_id = $eventMember->group_pupil_id;
                        $payment->event_member_id = $eventMember->id;
                        $payment->amount = $toCharge * (-1);

                        self::savePayment($payment);
                    }
                }
            }
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
        self::addPupilMoney($payment->user, $diff);
    }

    /**
     * @param Payment $payment
     * @param bool $logEvent
     * @throws \Exception
     * @throws \Throwable
     */
    public static function cancelPayment(Payment $payment, bool $logEvent = true)
    {
        self::addPupilMoney($payment->user, $payment->amount * (-1));
        if (!$payment->delete()) throw new \Exception('Error deleting payment from DB: ' . $payment->id);
        $paymentComment = 'Отмена списания за ' . $payment->createDate->format('d F Y') . ' в группе "' . $payment->groupPupil->group->name . '"';
        if ($logEvent && !\Yii::$app->actionLogger->log(
            $payment->user,
            Action::TYPE_CANCEL_AUTO,
            $payment->amount * (-1),
            $payment->groupPupil->group_id,
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
        self::addPupilMoney($payment->user, $payment->amount);
        $paymentComment = null;
        if ($payment->group_pupil_id) $paymentComment = 'Списание за ' . $payment->createDate->format('d F Y') . ' в группе "' . $payment->groupPupil->group->name . '"';
        if ($logEvent && !\Yii::$app->actionLogger->log(
            $payment->user,
            $actionType,
            $payment->amount,
            $payment->group_pupil_id ? $payment->groupPupil->group_id : null,
            $payment->comment ?: ($actionType == Action::TYPE_INCOME ? null : $paymentComment)
        )) throw new \Exception('Error logging payment: ' . $payment->id);
    }

    /**
     * @param User $user
     */
    public static function setUserChargeDates(User $user)
    {
        /** @var GroupPupil[] $groupPupils */
        $groupPupils = GroupPupil::find()->andWhere(['user_id' => $user->id])->with('group')->all();
        if (count($groupPupils) == 0) return;

        /*    Собираем информацию обо всех внесенных средствах    */
        $money = [
            0 => Payment::find()->andWhere(['user_id' => $user->id, 'group_pupil_id' => null])->select('SUM(amount)')->scalar(),
        ];
        $startDate = null;
        $groupPupilMap = [];
        foreach ($groupPupils as $groupPupil) {
            if (!$startDate || $startDate > $groupPupil->startDateObject) $startDate = $groupPupil->startDateObject;
            $money[$groupPupil->id] = Payment::find()->andWhere(['group_pupil_id' => $groupPupil->id])->andWhere(['>', 'amount', 0])->select('SUM(amount)')->scalar();
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
            ->andWhere([EventMember::tableName() . '.group_pupil_id' => array_keys($money), Event::tableName() . '.status' => [Event::STATUS_PASSED, Event::STATUS_UNKNOWN]])
            ->orderBy('event_date')
            ->all();

        foreach ($events as $event) {
            foreach ($event->members as $member) {
                if ($event->status == Event::STATUS_PASSED) {
                    foreach ($member->payments as $payment) {
                        $toCharge = $payment->amount * (-1);
                        if ($payment->used_payment_id && $money[$member->group_pupil_id] > 0) {
                            $money[$member->group_pupil_id] -= $toCharge;
                        } else {
                            $money[0] -= $toCharge;
                            if ($money[0] < 0) {
                                if (!$groupPupilMap[$member->group_pupil_id]['state']) {
                                    $groupPupilMap[$member->group_pupil_id]['entity']->date_charge_till = $event->event_date;
                                    $groupPupilMap[$member->group_pupil_id]['entity']->paid_lessons = 0;
                                    $groupPupilMap[$member->group_pupil_id]['state'] = true;
                                } else {
                                    $groupPupilMap[$member->group_pupil_id]['entity']->paid_lessons--;
                                }
                            }
                        }
                    }
                } else {
                    $param = GroupComponent::getGroupParam($event->group, $event->eventDateTime);
                    if ($money[$member->group_pupil_id] > 0) {
                        $money[$member->group_pupil_id] -= $param->lesson_price_discount;
                    } else {
                        $money[0] -= $param->lesson_price;
                        if ($money[0] < 0) {
                            if (!$groupPupilMap[$member->group_pupil_id]['state']) {
                                $groupPupilMap[$member->group_pupil_id]['entity']->date_charge_till = $event->event_date;
                                $groupPupilMap[$member->group_pupil_id]['entity']->paid_lessons = 0;
                                $groupPupilMap[$member->group_pupil_id]['state'] = true;
                            } else {
                                $groupPupilMap[$member->group_pupil_id]['entity']->paid_lessons--;
                            }
                        }
                    }
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
            $nowDate = new \DateTime('midnight');

            foreach ($groupPupilMap as $id => $item) {
                if (!$item['state'] && (!$item['entity']->date_end || $item['entity']->endDateObject > $nowDate)
                    && (!$item['entity']->group->date_end || $item['entity']->group->endDateObject > $nowDate)) {
                    $groupPupilMap[$id]['entity']->paid_lessons = 0;
                    $groupPupilMap[$id]['param'] = GroupComponent::getGroupParam($item['entity']->group, $nowDate);
                }
            }

            $currentDate = new \DateTime('midnight');
            $checkParam = true;
            while ($continue > 0) {
                if ($checkParam && $nowDate->format('Y-m') != $currentDate->format('Y-m')) {
                    foreach ($groupPupilMap as $id => $item) {
                        if (!$item['state']) {
                            $newParam = new GroupParam();
                            $newParam->lesson_price = $item['entity']->group->lesson_price;
                            $newParam->lesson_price_discount = $item['entity']->group->lesson_price_discount;
                            $newParam->weekday = $item['entity']->group->weekday;
                            $groupPupilMap[$id]['param'] = $newParam;
                        }
                    }
                    $checkParam = false;
                }

                $w = intval($currentDate->format('w'));
                foreach ($groupPupilMap as $id => $item) {
                    if (!$item['state']) {
                        if ($item['entity']->date_end && $item['entity']->endDateObject < $nowDate) {
                            $groupPupilMap[$id]['entity']->date_charge_till = $item['entity']->date_end;
                            $groupPupilMap[$id]['state'] = true;
                            $continue--;
                        } elseif ($item['entity']->group->date_end && $item['entity']->group->endDateObject < $nowDate) {
                            $groupPupilMap[$id]['entity']->date_charge_till = $item['entity']->group->date_end;
                            $groupPupilMap[$id]['state'] = true;
                            $continue--;
                        } elseif ($w > 0 && $item['param']->weekday[$w - 1] == '1') {
                            if ($money[$id] > 0) {
                                $money[$id] -= $item['param']->lesson_price_discount;
                                $groupPupilMap[$id]['entity']->paid_lessons++;
                            } else {
                                if ($money[0] > 0) $groupPupilMap[$id]['entity']->paid_lessons++;
                                $money[0] -= $item['param']->lesson_price;
                                if ($money[0] < 0) {
                                    $item['entity']->date_charge_till = $currentDate->format('Y-m-d H:i:s');
                                    $groupPupilMap[$id]['state'] = true;
                                    $continue--;
                                }
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

    /**
     * @param User $pupil
     * @param Group $group
     */
    public static function rechargePupil(User $pupil, Group $group)
    {
        $tomorrow = new \DateTime('+1 day midnight');

        /** @var GroupPupil[] $groupPupils */
        $groupPupils = GroupPupil::find()
            ->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id])
            ->orderBy('date_start')
            ->with(['eventMember' => function (ActiveQuery $query) {
                    $query->with(['event', 'payments']);
                }])
            ->all();
        if (!$groupPupils) return;

        /** @var Payment[] $payments */
        $payments = Payment::find()
            ->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id])
            ->andWhere(['>', 'amount', 0])
            ->orderBy('created_at')
            ->all();
        /** @var \SplDoublyLinkedList[] $paymentMap */
        $paymentMap = ['normal' => new \SplDoublyLinkedList(), 'discount' => new \SplDoublyLinkedList()];
        foreach ($payments as $payment) {
            if ($payment->discount == Payment::STATUS_ACTIVE) {
                $from = clone $payment->createDate;
                $from->modify('midnight');
                $paymentMap['discount']->push(['from' => $from, 'amount' => $payment->amount, 'entity' => $payment]);
            } else {
                $paymentMap['normal']->push(['amount' => $payment->amount, 'entity' => $payment]);
            }
        }

        $activeNormal = $activeDiscount = null;
        foreach ($groupPupils as $groupPupil) {

        }
        /** @var Payment[] $discountPayments */
        $discountPayments = Payment::find()
            ->andWhere(['group_pupil_id' => $groupPupil->id])
            ->andWhere(['>', 'amount', 0])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])->all();

        $discountPaymentList = [];
        $discountPaymentMap = [];
        foreach ($discountPayments as $discountPayment) {
            $from = clone $discountPayment->createDate;
            $from->modify('midnight');
            $discountPaymentList[] = ['id' => $discountPayment->id, 'from' => $from, 'amount' => $discountPayment->amount];
            $discountPaymentMap[$discountPayment->id] = $discountPayment->amount;
        }

        if ($groupPupil->date_end) $limitDate = clone $groupPupil->endDateObject;
        elseif ($groupPupil->group->date_end) $limitDate = clone $groupPupil->group->endDateObject;
        else $limitDate = $tomorrow;
        $limitDate->modify('midnight');
        if ($limitDate > $tomorrow) $limitDate = $tomorrow;

        /** @var Payment[] $paymentsToDelete */
        $paymentsToDelete = Payment::find()
            ->andWhere(['group_pupil_id' => $groupPupil->id])
            ->andWhere(['<', 'amount', 0])
            ->andWhere(['or', ['<', 'created_at', $groupPupil->date_start], ['>', 'created_at', $limitDate->format('Y-m-d H:i:s')]])->all();
        foreach ($paymentsToDelete as $paymentToDelete) {
            MoneyComponent::cancelPayment($paymentToDelete);
        }

        /** @var Event[] $events */
        $events = Event::find()
            ->andWhere(['group_id' => $groupPupil->group_id, 'status' => Event::STATUS_PASSED])
            ->andWhere(['>=', 'event_date', $groupPupil->date_start])
            ->andWhere(['<=', 'event_date', $limitDate->format('Y-m-d H:i:s')])
            ->with([
                'members' => function (ActiveQuery $query) use ($groupPupil) {
                    $query->andWhere(['group_pupil_id' => $groupPupil->id])
                        ->with('payments');
                },
            ])
            ->orderBy(['event_date' => SORT_ASC])
            ->all();

        $paymentStub = new Payment();
        $paymentStub->user_id = $groupPupil->user_id;
        $paymentStub->admin_id = \Yii::$app->user->id;
        $paymentStub->group_pupil_id = $groupPupil->id;

        $currentDiscount = null;
        foreach ($events as $event) {
            if (!$event->members) {
                $member = $event->addGroupPupil($groupPupil);
            } else {
                $member = $event->members[0];
            }
            if (!$currentDiscount && !empty($discountPaymentList) && $event->eventDateTime > $discountPaymentList[0]['from']) {
                $currentDiscount = array_shift($discountPaymentList);
            }
            $groupParam = GroupComponent::getGroupParam($groupPupil->group, $event->eventDateTime);

            /* Составляем очередь платежей которые нужно списать */
            /** @var Payment[] $paymentQueue */
            $paymentQueue = [];
            if ($currentDiscount) {
                $chargeAmount = $groupParam->lesson_price_discount;

                while ($currentDiscount && $chargeAmount > 0) {
                    $paymentSum = min($chargeAmount, $currentDiscount['amount']);
                    $payment = clone $paymentStub;
                    $payment->event_member_id = $member->id;
                    $payment->created_at = $event->event_date;
                    $payment->used_payment_id = $currentDiscount['id'];
                    $payment->amount = $paymentSum * (-1);
                    $paymentQueue[] = $payment;

                    $chargeAmount -= $paymentSum;
                    $currentDiscount['amount'] -= $paymentSum;
                    if ($currentDiscount['amount'] <= 0) {
                        $currentDiscount = null;
                        if (!empty($discountPaymentList) && $event->eventDateTime > $discountPaymentList[0]['from']) {
                            $currentDiscount = array_shift($discountPaymentList);
                        }
                    }
                }
                if ($chargeAmount > 0) {
                    $toCharge = intval(round(($chargeAmount / $groupParam->lesson_price_discount) * $groupParam->lesson_price));
                    $payment = clone $paymentStub;
                    $payment->event_member_id = $member->id;
                    $payment->created_at = $event->event_date;
                    $payment->amount = $toCharge * (-1);
                    $paymentQueue[] = $payment;
                }

            } else {
                $payment = clone $paymentStub;
                $payment->event_member_id = $member->id;
                $payment->created_at = $event->event_date;
                $payment->amount = $groupParam->lesson_price * (-1);
                $paymentQueue[] = $payment;
            }

            /* Проверяем если платежи из очереди уже списаны */
            if ($member->payments) {
                $donePayments = $member->payments;
                foreach ($paymentQueue as $payment) {
                    $found = false;
                    foreach ($donePayments as $key => $donePayment) {
                        if ($donePayment->amount == $payment->amount && $donePayment->used_payment_id == $payment->used_payment_id) {
                            $found = $key;
                            break;
                        }
                    }
                    if ($found) unset($donePayments[$found]);
                    else MoneyComponent::savePayment($payment);
                }
                foreach ($donePayments as $donePayment) MoneyComponent::cancelPayment($donePayment);
            } else {
                foreach ($paymentQueue as $payment) MoneyComponent::savePayment($payment);
            }
        }
    }
}