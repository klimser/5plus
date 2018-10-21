<?php

use yii\helpers\Html;
use \common\components\helpers\Calendar;

/* @var $this yii\web\View */
/* @var $salaryMap array */
/* @var $date \DateTime */

$this->title = 'Зарплата';
$this->params['breadcrumbs'][] = $this->title;
$monthInterval = new \DateInterval('P1M');
$prevMonth = clone $date;
$prevMonth->sub($monthInterval);
$nextMonth = clone $date;
$nextMonth->add($monthInterval);
?>
<div class="salary-index">
    <h1>
        <a href="<?= \yii\helpers\Url::to(['salary', 'year' => $prevMonth->format('Y'), 'month' => $prevMonth->format('n')]); ?>">
            <span class="glyphicon glyphicon-chevron-left"></span>
        </a>
        <?= Html::encode($this->title) ?> <?= Calendar::$monthNames[$date->format('n')]; ?> <?= $date->format('Y'); ?>
        <a href="<?= \yii\helpers\Url::to(['salary', 'year' => $nextMonth->format('Y'), 'month' => $nextMonth->format('n')]); ?>">
            <span class="glyphicon glyphicon-chevron-right"></span>
        </a>
    </h1>

    <?php foreach ($salaryMap as $payments): ?>
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title"><?= $payments[0]['teacher']; ?></h3>
            </div>
            <table class="table">
                <thead><tr><th>Группа</th><th class="text-right">Оплата</th><th></th></tr></thead>
                <tbody>
                    <?php
                        $totalSalary = 0;
                        foreach ($payments as $payment):
                            $totalSalary += $payment['amount']; ?>
                        <tr>
                            <td><?= $payment['group']; ?></td>
                            <td class="text-right"><?= $payment['amount']; ?></td>
                            <td><a href="<?= \yii\helpers\Url::to(['salary-details', 'group' => $payment['group_id'], 'year' => $date->format('Y'), 'month' => $date->format('n')]); ?>"><span class="glyphicon glyphicon-file"></span></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr><td><b>Итого</b></td><td class="text-right"><?= $totalSalary; ?></td><td></td></tr>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>