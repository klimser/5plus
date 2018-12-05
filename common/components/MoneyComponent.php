<?php

namespace common\components;

use common\models\Contract;
use common\models\Debt;
use backend\models\Event;
use backend\models\EventMember;
use common\models\Group;
use common\models\GroupParam;
use common\models\GroupPupil;
use common\models\Payment;
use common\models\User;
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
            self::savePayment($payment, Action::TYPE_INCOME, !empty(\Yii::$app->user->id));
            self::rechargePupil($payment->user, $payment->group);
            $transaction->commit();
            return $payment->id;
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            \Yii::$app->errorLogger->logError('money/income', $ex->getMessage(), true);
            throw $ex;
        }
    }

    /**
     * @param User $pupil
     * @param int $amount
     * @param bool $isDiscount
     * @param null|string $number
     * @param GroupPupil|null $groupPupil
     * @param Group|null $group
     * @return Contract
     * @throws \Exception
     */
    public static function addPupilContract(User $pupil, int $amount, bool $isDiscount, ?string $number, ?GroupPupil $groupPupil, ?Group $group): Contract
    {
        if ($amount <= 0) throw new \Exception('Сумма договора не может быть отрицательной');
        if ($pupil->role != User::ROLE_PUPIL) throw new \Exception('Договор может быть создан только для студента');
        if ($groupPupil === null && $group === null) throw new \Exception('Не выбрана группа');
        if ($groupPupil !== null && $groupPupil->user_id != $pupil->id) throw new \Exception('Введены неверные данные: pupil + groupPupil');

        $contract = new Contract();
        $contract->created_admin_id = \Yii::$app->user->id;
        $contract->user_id = $pupil->id;
        $contract->amount = $amount;
        $contract->discount = $isDiscount ? Contract::STATUS_ACTIVE : Contract::STATUS_INACTIVE;
        $contract->created_at = date('Y-m-d H:i:s');

        $groupParam = null;
        if ($groupPupil) {
            $contract->created_at = $groupPupil->date_start;
            $group = $groupPupil->group;
            if ($groupPupil->startDateObject->format('Y-m') <= date('Y-m')) {
                $groupParam = GroupComponent::getGroupParam($groupPupil->group, $groupPupil->startDateObject);
            }
        }
        $contract->group_id = $group->id;
        if ($isDiscount
            && (($groupParam && $amount < $groupParam->price3Month) || (!$groupParam && $amount < $group->price3Month))) {
            throw new \Exception('Договор по скидочной цене может быть не менее чем за 3 месяца');
        }

        if ($number) $contract->number = $number;
        else $contract = ContractComponent::generateContractNumber($contract);

        if (!$contract->save()) throw new \Exception('Не удалось создать договор: ' . $contract->getErrorsAsString());

        \Yii::$app->actionLogger->log(
            $pupil,
            Action::TYPE_CONTRACT_ADDED,
            $contract->amount,
            $contract->group
        );

        return $contract;
    }

    /**
     * @param Contract $contract
     * @param \DateTime $payDate
     * @param int $payType
     * @param null|string $paymentComment
     * @return int
     * @throws \Exception
     */
    public static function payContract(Contract $contract, \DateTime $payDate, int $payType, ?string $paymentComment = null): int
    {
        if ($contract->status == Contract::STATUS_PAID) throw new \Exception('Договор уже оплачен!');

        $payment = new Payment();
        $payment->admin_id = \Yii::$app->user->id;
        $payment->user_id = $contract->user_id;
        $payment->group_id = $contract->group_id;
        $payment->amount = $contract->amount;
        $payment->discount = $contract->discount;
        $payment->contract_id = $contract->id;
        $payment->created_at = $payDate->format('Y-m-d H:i:s');
        if ($paymentComment) $payment->comment = $paymentComment;

        $contract->status = Contract::STATUS_PAID;
        $contract->payment_type = $payType;
        $contract->paid_admin_id = \Yii::$app->user->id;
        $contract->paid_at = $payDate->format('Y-m-d H:i:s');

        if (!$contract->save()) throw new \Exception('Contract save error: ' . $contract->getErrorsAsString());

        $groupPupil = GroupPupil::find()
            ->andWhere(['user_id' => $contract->user_id, 'group_id' => $contract->group_id, 'active' => GroupPupil::STATUS_ACTIVE])
            ->one();
        if (!$groupPupil) {
            GroupComponent::addPupilToGroup($contract->user, $contract->group, $payDate);
        }

        $paymentId = self::registerIncome($payment);
        $contract->link('payments', $payment);
        \Yii::$app->actionLogger->log(
            $contract->user,
            Action::TYPE_CONTRACT_PAID,
            $contract->amount,
            $contract->group
        );
        self::setUserChargeDates($contract->user, $contract->group);

        return $paymentId;
    }

    /**
     * @param User $user
     * @param Group $group
     * @return bool
     */
    public static function recalculateDebt(User $user, Group $group): bool
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

                foreach ($event->members as $eventMember) {
                    if (!$eventMember->payments) {
                        $rate = 1;
                        $groupParam = GroupComponent::getGroupParam($event->group, $event->eventDateTime);

                        while ($rate > 0) {
                            $payment = clone $paymentStub;
                            $payment->user_id = $eventMember->groupPupil->user_id;
                            $payment->event_member_id = $eventMember->id;

                            /** @var Payment $parentPayment */
                            $parentPayment = Payment::find()
                                ->alias('p')
                                ->andWhere(['p.user_id' => $eventMember->groupPupil->user_id, 'p.group_id' => $event->group_id])
                                ->andWhere(['>', 'p.amount', 0])
                                ->joinWith('payments ch')
                                ->select(['p.id', 'p.amount', 'SUM(ch.amount) as spent'])
                                ->groupBy(['p.id', 'p.amount'])
                                ->orderBy(['p.created_at' => SORT_ASC])
                                ->having('spent IS NULL OR p.amount > (spent * -1)')
                                ->one();

                            if ($parentPayment) {
                                $isDiscount = $groupParam->lesson_price_discount && $parentPayment->discount;
                                $lessonPrice = $isDiscount ? $groupParam->lesson_price_discount : $groupParam->lesson_price;
                                $toPay = round($rate * $lessonPrice);

                                if ($parentPayment->paymentsSum >= $toPay) {
                                    $payment->amount = $toPay * (-1);
                                    $payment->discount = $isDiscount;
                                    $rate = 0;
                                } else {
                                    $payment->amount = ($parentPayment->amount - $parentPayment->paymentsSum) * (-1);
                                    $payment->discount = $isDiscount;
                                    $rate -= ($payment->amount * (-1)) / $lessonPrice;
                                }
                                $payment->used_payment_id = $parentPayment->id;
                            } else {
                                $toPay = round($rate * $groupParam->lesson_price);
                                $payment->amount = $toPay * (-1);
                                $payment->discount = 0;
                                $rate = 0;
                            }
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
                    if ($payment->discount && $moneyDiscount > 0) {
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

        /** @var GroupParam[] $groupParams */
        $groupParams = GroupParam::find()->andWhere(['group_id' => $group->id])->all();
        /** @var GroupParam[] $groupParamMap */
        $groupParamMap = [];
        foreach ($groupParams as $groupParam) {
            $groupParamMap["{$groupParam->year}-{$groupParam->month}"] = $groupParam;
        }

        $sortPayments = function($a, $b) {
            if ($a['id'] > $b['id']) return -1;
            if ($a['id'] < $b['id']) return 1;
            return $a['amount'] - $b['amount'];
        };

        foreach ($groupPupils as $groupPupil) {
            foreach ($groupPupil->eventMembers as $eventMember) {
                $key = $eventMember->event->eventDateTime->format('Y') . '-' . $eventMember->event->eventDateTime->format('n');
                if (!array_key_exists($key, $groupParamMap)) {
                    $groupParamMap[$key] = GroupComponent::getGroupParam($group, $eventMember->event->eventDateTime);
                }

                $rate = 1;
                $toCharge = [];
                while ($rate > 0) {
                    if (!empty($payments)) {
                        $lessonPrice = $payments[0]['discount'] ? $groupParamMap[$key]->lesson_price_discount : $groupParamMap[$key]->lesson_price;

                        $toPay = round($rate * $lessonPrice);
                        if ($payments[0]['amount'] >= $toPay) {
                            $toCharge[] = ['id' => $payments[0]['id'], 'discount' => $payments[0]['discount'], 'amount' => $toPay];
                            $payments[0]['amount'] -= $toPay;
                            $rate = 0;
                        } else {
                            $toCharge[] = ['id' => $payments[0]['id'], 'discount' => $payments[0]['discount'], 'amount' => $payments[0]['amount']];
                            $rate -= $payments[0]['amount'] / $lessonPrice;
                            $payments[0]['amount'] = 0;
                        }

                        if ($payments[0]['amount'] <= 0) array_shift($payments);
                    } else {
                        $toCharge[] = ['id' => null, 'discount' => 0, 'amount' => round($rate * $groupParamMap[$key]->lesson_price)];
                        $rate = 0;
                    }
                }

                usort($toCharge, $sortPayments);
                $charged = [];
                foreach ($eventMember->payments as $payment) {
                    $charged[] = ['id' => $payment->used_payment_id, 'discount' => $payment->discount, 'amount' => $payment->amount * (-1)];
                }
                usort($charged, $sortPayments);
                if ($toCharge != $charged) {
                    foreach ($eventMember->payments as $payment) {
                        self::cancelPayment($payment);
                    }
                    foreach ($toCharge as $paymentData) {
                        $payment = new Payment();
                        $payment->user_id = $pupil->id;
                        $payment->admin_id = \Yii::$app->user->id;
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