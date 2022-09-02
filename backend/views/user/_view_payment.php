<?php

use common\components\helpers\MoneyHelper;

/* @var $this \yii\web\View */
/* @var $student \common\models\User */

/** @var \common\models\Payment[] $payments */
$payments = $student->getPayments()->with('course')->all();
/** @var \common\models\Course[] $courseMap */
$courseMap = [];
foreach ($payments as $payment) {
    if (!array_key_exists($payment->course_id, $courseMap)) {
        $courseMap[$payment->course_id] = $payment->course;
    }
}

?>

<div class="view-payments">
    <div class="row mb-3">
        <div class="col-8">
            <div class="form-inline">
                <label for="payment-filter-course-<?= $student->id; ?>">Группа</label>
                <select id="payment-filter-course-<?= $student->id; ?>" class="form-control filter-course ml-2" onchange="Dashboard.filterPayments(this);">
                    <option value="0">все</option>
                    <?php foreach ($courseMap as $course): ?>
                        <option value="<?= $course->id; ?>"><?= $course->courseConfig->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-4">
            <div class="form-check">
                <input class="form-check-input filter-type" type="checkbox" value="1" onchange="Dashboard.filterPayments(this);" id="filter-payment-<?= $student->id; ?>">
                <label class="form-check-label" for="filter-payment-<?= $student->id; ?>">
                    показать расходы
                </label>
            </div>
        </div>
    </div>
    <table class="table table-bordered table-sm table-responsive-md payments-table">
        <thead>
            <tr>
                <th>группа</th>
                <th>дата</th>
                <th class="text-right">сумма</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr class="course-<?= $payment->course_id; ?> collapse table-<?php
                    if ($payment->amount > 0) {
                        echo $payment->discount ? 'info' : 'success', ' show ';
                    } elseif ($payment->amount < 0) {
                        echo $payment->discount ? 'warning' : 'danger', ' expense ';
                    }
                    ?>">
                    <td>
                        <?= $payment->course->courseConfig->name; ?>
                        <?php if ($payment->comment): ?>
                            <span class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" data-html="true" title="<?= $payment->comment; ?>"></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $payment->createDate->format('d.m.Y'); ?></td>
                    <td class="text-right"><?= MoneyHelper::formatThousands($payment->amount); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
