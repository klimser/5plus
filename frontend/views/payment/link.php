<?php

use yii\helpers\Url;
use yii\web\View;

/* @var $this \frontend\components\extended\View */
/* @var $paymentLink \common\models\PaymentLink|null */
/* @var $courseStudents \common\models\CourseStudent[] */

$this->params['breadcrumbs'][] = ['url' => Url::to(['payment/index']), 'label' => 'Онлайн оплата'];
$this->params['breadcrumbs'][] = $paymentLink !== null ? $paymentLink->user->nameHidden : 'Не найден';

if ($paymentLink) {
    $script ="
        Payment.users[{$paymentLink->user_id}] = {
            name: '{$paymentLink->user->nameHidden}',
            courses: []
        };
        Payment.user = {$paymentLink->user_id};
    ";
    $this->registerJs($script, View::POS_END);
}
?>

<div class="container">
    <div class="content-box payment-panel">
        <?php if (!$paymentLink): ?>
            <h3>Неверная ссылка!</h3>
        <?php else: ?>
            <div id="payment-<?= $paymentLink->course_id; ?>" class="course-payments" data-courseid="<?= $paymentLink->course_id; ?>" data-coursename="<?= $paymentLink->course->courseConfig->legal_name; ?>">
                <br>
                <?php
                    $debt = $paymentLink->user->getDebt($paymentLink->course);
                    $debt = $debt ? $debt->amount : 0;
                    $payDate = null;
                    if (!$debt) {
                        foreach ($courseStudents as $courseStudent) {
                            if ($courseStudent->chargeDateObject && ($payDate === null || $payDate > $courseStudent->chargeDateObject)) {
                                $payDate = $courseStudent->chargeDateObject;
                            }
                        }
                    }
                ?>
                <h4>
                    <?= $paymentLink->user->nameHidden; ?> | <?= $paymentLink->course->courseConfig->legal_name; ?> <?= $debt ? '' : '<small>оплачено до ' . ($payDate ? $payDate->format('d.m.Y') : '') . '</small>'; ?>
                </h4>
                <div class="row">
                    <?php if ($debt > 0): ?>
                        <div class="col-12 col-md-auto mb-2">
                            <button class="btn btn-primary btn-block" data-sum="<?= $debt; ?>" onclick="Payment.selectSum(this);">
                                Погасить задолженность <?= $debt; ?> сум
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="col-12 col-md-auto mb-2">
                        <button class="btn btn-secondary btn-block" data-sum="<?= $paymentLink->course->courseConfig->lesson_price; ?>" data-limit="<?= $paymentLink->course->courseConfig->price12Lesson; ?>" onclick="Payment.selectSum(this);">
                            за 1 занятие <?= $paymentLink->course->courseConfig->lesson_price; ?> сум
                        </button>
                    </div>
                    <div class="col-12 col-md-auto mb-2">
                        <button class="btn btn-secondary btn-block" data-sum="<?= $paymentLink->course->courseConfig->priceMonth; ?>" data-limit="<?= $paymentLink->course->courseConfig->price12Lesson; ?>" onclick="Payment.selectSum(this);">
                            за 1 месяц <?= $paymentLink->course->courseConfig->priceMonth; ?> сум
                        </button>
                    </div>
                    <div class="col-12 col-md-auto mb-2">
                        <button class="btn btn-secondary btn-block" data-sum="none" data-limit="<?= $paymentLink->course->courseConfig->price12Lesson; ?>" onclick="Payment.selectSum(this);">другая сумма</button>
                    </div>
                </div>
            </div>

            <?= $this->render('_modal'); ?>
        <?php endif; ?>
    </div>
</div>
