<?php

use yii\bootstrap4\Html;

/* @var $this yii\web\View */
/* @var $student \common\models\User */
/* @var $course \common\models\Course */
/* @var $debt \common\models\Debt|null */

$this->title = 'Погашение долга';
$this->params['breadcrumbs'][] = ['label' => 'Долги', 'url' => ['debt']];
$this->params['breadcrumbs'][] = $this->title;

?>

<h1><?= Html::encode($this->title) ?></h1>

<div id="messages_place"></div>

<h2><?= $student->name; ?> <small><?= $course->courseConfig->name; ?></small> <?= $debt ? $debt->amount . ' сум' : ''; ?></h2>

<?= Html::beginForm(); ?>

<div class="form-group">
    <div class="input-group">
        <input id="amount" name="payment_sum" type="number" min="1000" step="1000" class="form-control input-lg" placeholder="Сумма оплаты" required>
        <div class="input-group-append"><div class="input-group-text">сум</div></div>
    </div>
</div>
<div class="form-group">
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input" type="radio" name="cash_received" value="0" checked> Деньги не получены, фиксируем убыток.
        </label>
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input" type="radio" name="cash_received" value="1"> Деньги получены.
        </label>
    </div>
</div>
<div class="form-group">
    <button class="btn btn-success btn-lg" id="income_button">внести</button>
</div>

<?= Html::endForm(); ?>
