<?php

use yii\bootstrap4\Html;

/* @var $this yii\web\View */
/* @var $pupil \common\models\User */
/* @var $group \common\models\Group */
/* @var $debt \common\models\Debt|null */

$this->title = 'Погашение долга';
$this->params['breadcrumbs'][] = ['label' => 'Долги', 'url' => ['debt']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row money-income">
    <h1><?= Html::encode($this->title) ?></h1>

    <div id="messages_place"></div>

    <h2><?= $pupil->name; ?> <small><?= $group->name; ?></small> <?= $debt ? $debt->amount . ' сум' : ''; ?></h2>
    <div class="col-xs-12">
        <?= Html::beginForm(); ?>

        <div class="form-group">
            <div class="input-group">
                <input id="amount" name="payment_sum" type="number" min="1000" step="1000" class="form-control input-lg" placeholder="Сумма оплаты" required>
                <div class="input-group-addon">сум</div>
            </div>
        </div>
        <div class="radio">
            <label>
                <input type="radio" name="cash_received" value="0" checked> Деньги не получены, фиксируем убыток.
            </label>
        </div>
        <div class="radio">
            <label>
                <input type="radio" name="cash_received" value="1"> Деньги получены.
            </label>
        </div>
        <div class="form-group">
            <button class="btn btn-success btn-lg" id="income_button">внести</button>
        </div>

        <?= Html::endForm(); ?>
    </div>
</div>
