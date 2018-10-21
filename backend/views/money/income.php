<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user \backend\models\User */

$this->registerJs(<<<SCRIPT
    Money.init();
SCRIPT
);
$searchValue = '';
if (isset($user)) {
    $this->registerJs(<<<SCRIPT
    Money.findCustomer();
SCRIPT
    );
    $searchValue = $user->phoneFormatted;
}

$this->title = 'Внесение оплаты';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row money-income">
    <h1><?= Html::encode($this->title) ?></h1>

    <div id="messages_place"></div>

    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            <label for="payment_date">Дата платежа</label>
            <?= \dosamigos\datepicker\DatePicker::widget([
                'name' => 'payment_date',
                'value' => date('d.m.Y'),
                'options' => ['id' => 'payment_date'],
                'clientOptions' => [
                    'autoclose' => true,
                    'format' => 'dd.mm.yyyy',
                    'language' => 'ru',
                    'weekStart' => 1,
                ]
            ]);?>
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            <label for="comment">Комментарий к платежу</label>
            <input id="comment" class="form-control">
        </div>
    </div>
    <div class="col-xs-12">
        <div class="form-group">
            <label for="contract">Номер договора</label>
            <input id="contract" class="form-control">
        </div>
    </div>

    <div class="col-xs-12">
        <form onsubmit="Money.findCustomer(); return false;">
            <div class="input-group input-group-lg">
                <span class="input-group-addon">+998</span>
                <input id="search_phone" class="form-control" placeholder="Телефон ученика или родителей" required minlength="11" maxlength="11" pattern="\d{2} \d{3}-\d{4}" value="<?= $searchValue; ?>">
                <span class="input-group-btn">
                    <button class="btn btn-success">Искать</button>
                </span>
            </div>
        </form>
        <hr>
        <div id="search_results_block"></div>
    </div>
</div>