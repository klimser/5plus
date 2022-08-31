<?php

namespace common\components;

use common\models\Course;
use common\models\PaymentLink;
use common\models\User;

class PaymentComponent
{
    /**
     * @param int $userId
     * @param int $groupId
     * @return PaymentLink
     * @throws \Exception
     */
    public static function getPaymentLink(int $userId, int $groupId): PaymentLink
    {
        $paymentLink = PaymentLink::findOne(['user_id' => $userId, 'group_id' => $groupId]);
        if ($paymentLink) return $paymentLink;

        $pupil = User::findOne($userId);
        $group = Course::findOne($groupId);
        if (!$pupil || !$group || $pupil->role != User::ROLE_STUDENT) throw new \Exception('Unable to create payment link');

        $str = $pupil->name . $pupil->id . $group->id;
        $len = 6;
        $i = 1;
        while (true) {
            $hash = substr(md5($str), 0, $len);
            $paymentLink = new PaymentLink();
            $paymentLink->user_id = $pupil->id;
            $paymentLink->group_id = $group->id;
            $paymentLink->hash_key = $hash;
            if ($paymentLink->save()) return $paymentLink;
            $str .= rand(0, 9);
            $i++;
            if ($i > 10) {
                $len++;
                $i = 1;
            }
        }
        throw new \Exception('Unable to create payment link');
    }
}