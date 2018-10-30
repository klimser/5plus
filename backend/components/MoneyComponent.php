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
     * @param Group $group
     * @throws \Exception
     */
    protected static function addPupilMoney(User $pupil, int $amount, Group $group)
    {
        if ($pupil->role != User::ROLE_PUPIL) throw new \Exception('Money can be changed only for pupils');

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
        $transaction = \Yii::$app->getDb()->beginTransaction();
        try {
            self::savePayment($payment, Action::TYPE_INCOME);
            if ($payment->discount == Payment::STATUS_ACTIVE) {
                MoneyComponent::rechargePupil($payment->user, $payment->group);
                if (!self::recalculateDebt($payment->user, $payment->group)) throw new \Exception('Error on pupil\'s debt calculation');
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
     * @param Group $group
     * @return bool
     */
    public static function recalculateDebt(User $user, Group $group): bool
    {
        $balance = Payment::find()
            ->andWhere(['user_id' => $user->id, 'group_id' => $group->id, 'discount' => Payment::STATUS_INACTIVE])
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
            $debt->group_id = $group->id;
            $debt->amount = $newDebt;
            return $debt->save();
        }
        return true;
    }

    /**
     * @param Event $event
     * @throws \Exception
     */
    public static function chargeByEvent(Event $event)
    {
        switch ($event->status) {
            case Event::STATUS_UNKNOWN:
                return;
            case Event::STATUS_PASSED:
                $paymentStub = new Payment();
                $paymentStub->admin_id = \Yii::$app->user->id;
                $paymentStub->group_id = $event->group_id;
                $paymentStub->created_at = $event->event_date;
                $limitDate = clone $event->eventDateTime;
                $limitDate->modify('+1 day midnight');

                foreach ($event->members as $eventMember) {
                    if (!$eventMember->payments) {
                        $rate = 1;
                        $groupParam = GroupComponent::getGroupParam($event->group, $event->eventDateTime);
                        if ($groupParam->lesson_price_discount) {
                            /** @var Payment[] $discountPayments */
                            $discountPayments = Payment::find()
                                ->andWhere([
                                    'discount' => Payment::STATUS_ACTIVE,
                                    'user_id' => $eventMember->groupPupil->user_id,
                                    'group_id' => $event->group_id,
                                ])
                                ->andWhere(['<', 'created_at', $limitDate->format('Y-m-d H:i:s')])
                                ->orderBy(['created_at' => SORT_ASC])
                                ->all();

                            foreach ($discountPayments as $discountPayment) {
                                $left = $discountPayment->amount - $discountPayment->paymentsSum;
                                if ($left > 0) {
                                    $amount = round($groupParam->lesson_price_discount * $rate);
                                    if ($left < $amount) {
                                        $amount = $left;
                                        $rate -= $amount / $groupParam->lesson_price_discount;
                                    } else $rate = 0;

                                    $payment = clone $paymentStub;
                                    $payment->user_id = $eventMember->groupPupil->user_id;
                                    $payment->event_member_id = $eventMember->id;
                                    $payment->used_payment_id = $discountPayment->id;
                                    $payment->amount = $amount * (-1);

                                    self::savePayment($payment);
                                }
                                if ($rate == 0) break;
                            }
                        }
                        if ($rate > 0) {
                            $payment = clone $paymentStub;
                            $payment->user_id = $eventMember->groupPupil->user_id;
                            $payment->event_member_id = $eventMember->id;
                            $payment->amount = round($groupParam->lesson_price * $rate) * (-1);

                            self::savePayment($payment);
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
        self::addPupilMoney($payment->user, $payment->amount * (-1), $payment->group);
        if (!$payment->delete()) throw new \Exception('Error deleting payment from DB: ' . $payment->id);
        $paymentComment = 'Отмена списания за ' . $payment->createDate->format('d F Y') . ' в группе "' . $payment->group->name . '"';
        if ($logEvent && !\Yii::$app->actionLogger->log(
            $payment->user,
            Action::TYPE_CANCEL_AUTO,
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
        if ($logEvent && !\Yii::$app->actionLogger->log(
            $payment->user,
            $actionType,
            $payment->amount,
            $payment->group,
            $payment->comment ?: ($actionType == Action::TYPE_INCOME ? null : $paymentComment)
        )) throw new \Exception('Error logging payment: ' . $payment->id);
    }

    /**
     * @param User $user
     * @param Group $group
     */
    public static function setUserChargeDates(User $user, Group $group)
    {
        /** @var GroupPupil[] $groupPupils */
        $groupPupils = GroupPupil::find()->andWhere(['user_id' => $user->id, 'group_id' => $group->id])->all();
        if (count($groupPupils) == 0) return;

        /*    Собираем информацию обо всех внесенных средствах    */
        $money = Payment::find()
            ->andWhere(['user_id' => $user->id, 'group_id' => $group->id, 'discount' => Payment::STATUS_INACTIVE])
            ->andWhere(['>', 'amount', 0])
            ->select('SUM(amount)')->scalar();
        $moneyDiscount = Payment::find()
            ->andWhere(['user_id' => $user->id, 'group_id' => $group->id, 'discount' => Payment::STATUS_ACTIVE])
            ->andWhere(['>', 'amount', 0])
            ->select('SUM(amount)')->scalar();
        $startDate = null;
        $groupPupilMap = [];
        foreach ($groupPupils as $groupPupil) {
            if (!$startDate || $startDate > $groupPupil->startDateObject) $startDate = $groupPupil->startDateObject;
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
                    $toCharge = $payment->amount * (-1);
                    if ($payment->used_payment_id && $moneyDiscount > 0) {
                        $moneyDiscount -= $toCharge;
                    } else {
                        $money -= $toCharge;
                        if ($money < 0) {
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
                if (!$item['state'] && (!$item['entity']->date_end || $item['entity']->endDateObject >= $nowDate)
                    && (!$item['entity']->group->date_end || $item['entity']->group->endDateObject >= $nowDate)) {
                    $item['entity']->paid_lessons = 0;
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
                            $item['entity']->date_charge_till = $item['entity']->date_end;
                            $groupPupilMap[$id]['state'] = true;
                            $continue--;
                        } elseif ($item['entity']->group->date_end && $item['entity']->group->endDateObject < $nowDate) {
                            $item['entity']->date_charge_till = $item['entity']->group->date_end;
                            $groupPupilMap[$id]['state'] = true;
                            $continue--;
                        } elseif ($w > 0 && $item['param']->weekday[$w - 1] == '1') {
                            if ($item['param']->lesson_price_discount && $moneyDiscount > 0) {
                                $moneyDiscount -= $item['param']->lesson_price_discount;
                                $item['entity']->paid_lessons++;
                            } else {
                                if ($money > 0) $item['entity']->paid_lessons++;
                                $money -= $item['param']->lesson_price;
                                if ($money < 0) {
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
        /** @var GroupPupil[] $groupPupils */
        $groupPupils = GroupPupil::find()
            ->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id])
            ->orderBy('date_start')
            ->with(['eventMembers' => function (ActiveQuery $query) {
                    $query
                        ->joinWith('event')
                        ->andWhere([Event::tableName() . '.status' => Event::STATUS_PASSED])
                        ->with(['event', 'payments']);
                }])
            ->all();
        if (!$groupPupils) return;

        /** @var Payment[] $payments */
        $payments = Payment::find()
            ->andWhere(['user_id' => $pupil->id, 'group_id' => $group->id, 'discount' => Payment::STATUS_ACTIVE])
            ->andWhere(['>', 'amount', 0])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
        $paymentMap = [];
        foreach ($payments as $payment) {
            $from = clone $payment->createDate;
            $from->modify('midnight');
            $paymentMap[] = ['id' => $payment->id, 'from' => $from, 'amount' => $payment->amount];
        }

        /** @var GroupParam[] $groupParams */
        $groupParams = GroupParam::find()->andWhere(['group_id' => $group->id])->all();
        $groupParamMap = [];
        foreach ($groupParams as $groupParam) {
            $groupParamMap["{$groupParam->year}-{$groupParam->month}"] = $groupParam;
        }

        $getChargeList = function(Group $group, Event $event) use (&$paymentMap, &$groupParamMap) {
            $key = $event->eventDateTime->format('Y') . '-' . $event->eventDateTime->format('n');
            if (!array_key_exists($key, $groupParamMap)) {
                $groupParamMap[$key] = GroupComponent::getGroupParam($group, $event->eventDateTime);
            }

            $rate = 1;
            $payments = [];
            if (!empty($paymentMap)) {
                foreach ($paymentMap as &$payment) {
                    if ($payment['amount'] > 0 && $payment['from'] <= $event->eventDateTime) {
                        $amount = round($groupParamMap[$key]->lesson_price_discount * $rate);
                        if ($payment['amount'] < $amount) {
                            $amount = $payment['amount'];
                            $rate -= $amount / $groupParamMap[$key]->lesson_price_discount;
                        } else $rate = 0;
                        $payments[] = [
                            'id' => $payment['id'],
                            'amount' => $amount,
                        ];
                        $payment['amount'] -= $amount;
                        if ($rate == 0) break;
                    }
                }
            }
            if ($rate > 0) {
                $payments[] = ['id' => null, 'amount' => round($groupParamMap[$key]->lesson_price * $rate)];
            }

            return $payments;
        };

        $sortPayments = function($a, $b) {
            if ($a['id'] > $b['id']) return -1;
            if ($a['id'] < $b['id']) return 1;
            return $a['amount'] - $b['amount'];
        };

        $activePayment = null;
        foreach ($groupPupils as $groupPupil) {
            foreach ($groupPupil->eventMembers as $eventMember) {
                $toCharge = $getChargeList($group, $eventMember->event);
                usort($toCharge, $sortPayments);
                $charged = [];
                foreach ($eventMember->payments as $payment) {
                    $charged[] = ['id' => $payment->used_payment_id, 'amount' => $payment->amount * (-1)];
                }
                usort($charged, $sortPayments);
                if ($toCharge != $charged) {
                    foreach ($eventMember->payments as $payment) {
                        MoneyComponent::cancelPayment($payment);
                    }
                    foreach ($toCharge as $paymentData) {
                        $payment = new Payment();
                        $payment->user_id = $pupil->id;
                        $payment->admin_id = \Yii::$app->user->id;
                        $payment->group_id = $group->id;
                        $payment->event_member_id = $eventMember->id;
                        $payment->created_at = $eventMember->event->event_date;
                        $payment->used_payment_id = $paymentData['id'];
                        $payment->amount = $paymentData['amount'] * (-1);
                        MoneyComponent::savePayment($payment);
                    }
                }
            }
        }
    }
}