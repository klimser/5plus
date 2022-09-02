<?php

namespace common\components;

use common\models\Course;
use common\models\PaymentLink;
use common\models\User;

class PaymentComponent
{
    /**
     * @param int $userId
     * @param int $courseId
     *
     * @return PaymentLink
     * @throws \Exception
     */
    public static function getPaymentLink(int $userId, int $courseId): PaymentLink
    {
        $paymentLink = PaymentLink::findOne(['user_id' => $userId, 'course_id' => $courseId]);
        if ($paymentLink) return $paymentLink;

        $student = User::findOne($userId);
        $course = Course::findOne($courseId);
        if (!$student || !$course || $student->role != User::ROLE_STUDENT) throw new \Exception('Unable to create payment link');

        $str = $student->name . $student->id . $course->id;
        $len = 6;
        $i = 1;
        while (true) {
            $hash = substr(md5($str), 0, $len);
            $paymentLink = new PaymentLink();
            $paymentLink->user_id = $student->id;
            $paymentLink->course_id = $course->id;
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