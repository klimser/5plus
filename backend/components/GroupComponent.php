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
     * @param GroupPupil $groupPupil
     * @throws \Exception
     */
    public static function rechargeGroupPupil(GroupPupil $groupPupil)
    {
        $tomorrow = new \DateTime('+1 day midnight');

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
        $from = $group->startDateObject;
        $from->modify('first day of ' . $from->format('F Y') . ' midnight');
        $limit = new \DateTime('first day of next month midnight');
        $to = $group->endDateObject;
        if ($to) {
            $to->modify('first day of ' . $to->format('F Y') . ' midnight');
            $to->add($monthInterval);
        }
        if (!$to || $to > $limit) $to = $limit;

        if ($group->groupPupils) {
            $dateStart = clone $from;
            $dateEnd = clone $dateStart;
            $dateEnd->add($monthInterval);
            $groupPupilIds = [];
            foreach ($group->groupPupils as $groupPupil) $groupPupilIds[] = $groupPupil->id;

            while ($dateEnd <= $to) {
                $groupParam = self::getGroupParam($group, $dateStart);

                $paymentSum = Payment::find()
                    ->andWhere(['<', 'amount', 0])
                    ->andWhere(['group_pupil_id' => $groupPupilIds])
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