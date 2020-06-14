<?php

use common\components\helpers\Money;

/* @var $this \yii\web\View */
/* @var $pupil \common\models\User */

/** @var \common\models\Payment[] $payments */
$payments = $pupil->getPayments()->with('group')->all();
/** @var \common\models\Group[] $groupMap */
$groupMap = [];
foreach ($payments as $payment) {
    if (!array_key_exists($payment->group_id, $groupMap)) {
        $groupMap[$payment->group_id] = $payment->group;
    }
}

?>

<div class="view-payments m-t-10">
    <div class="row">
        <div class="col-xs-8">
            <div class="form-inline">
                <label for="payment-filter-group-<?= $pupil->id; ?>">Группа</label>
                <select id="payment-filter-group-<?= $pupil->id; ?>" class="form-control filter-group" onchange="Dashboard.filterPayments(this);">
                    <option value="0">все</option>
                    <?php foreach ($groupMap as $group): ?>
                        <option value="<?= $group->id; ?>"><?= $group->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-xs-4">
            <div class="checkbox">
                <label>
                    <input type="checkbox" class="filter-type" value="1" onchange="Dashboard.filterPayments(this);"> показать расходы
                </label>
            </div>
        </div>
    </div>
    <table class="table table-condensed payments-table">
        <thead>
            <tr>
                <th>группа</th>
                <th>дата</th>
                <th class="text-right">сумма</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr class="group-<?= $payment->group_id; ?> <?php
                    if ($payment->amount > 0) {
                        echo $payment->discount ? 'info' : 'success';
                    } elseif ($payment->amount < 0) {
                        echo ' hidden expense ', $payment->discount ? 'warning' : 'danger';
                    }
                    ?>">
                    <td><?= $payment->group->name; ?></td>
                    <td><?= $payment->createDate->format('d.m.Y'); ?></td>
                    <td class="text-right"><?= Money::formatThousands($payment->amount); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
