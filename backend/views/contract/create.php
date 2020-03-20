<?php

use yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $user \common\models\User */

$this->registerJs(<<<SCRIPT
    Main.initPhoneFormatted();
    Contract.loadGroups();
    Money.className = 'Contract';
SCRIPT
);
$searchValue = '';
if (isset($user)) {
    $this->registerJs(<<<SCRIPT
    Money.findPupils();
SCRIPT
    );
    $searchValue = $user->phoneFormatted;
}

$this->title = 'Новый договор';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row money-income">
    <h1><?= Html::encode($this->title) ?></h1>

    <div id="messages_place"></div>

    <div class="col-xs-12">
        <form onsubmit="Money.findPupils(); return false;">
            <div class="input-group input-group-lg">
                <span class="input-group-addon">+998</span>
                <input id="search_phone" class="form-control phone-formatted" placeholder="Телефон ученика или родителей" required minlength="11" maxlength="11" pattern="\d{2} \d{3}-\d{4}" value="<?= $searchValue; ?>" <?= $searchValue ? 'autofocus' : ''; ?>>
                <span class="input-group-btn">
                    <button class="btn btn-success">Искать</button>
                </span>
            </div>
        </form>
    </div>
    <?= Html::beginForm('', 'post', ['onsubmit' => 'return Contract.complete(this);']); ?>
        <input type="hidden" name="user_id" id="user_input">
        <input type="hidden" name="group_id" id="group_input">
        <input type="hidden" name="discount" id="discount_input">
        <div class="col-xs-12 phone-search-result" id="pupils_block"></div>
        <div class="col-xs-12 phone-search-result" id="groups_block"></div>
        <div class="col-xs-12 hidden" id="payment_type_block">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <button class="btn btn-default btn-lg full-width" type="button" id="payment-0" onclick="Contract.setPayment(0);">Без скидки<br><small><span class="price"></span> в месяц</small></button>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <button class="btn btn-default btn-lg full-width" type="button" id="payment-1" onclick="Contract.setPayment(1);">Со скидкой<br><small><span class="price"></span> за 3 месяца</small></button>
                        </div>
                    </div>
                    <div class="row hidden" id="group_dates">
                        <div class="col-xs-12"><br>
                            Занимается с <span class="big-font" id="date_start"></span><br>
                            Оплачено до <span class="big-font" id="date_charge_till"></span><br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="income_form" class="col-xs-12 hidden">
            <div class="form-group"><div class="input-group"><input id="amount" name="amount" type="number" min="1000" step="1000" class="form-control input-lg" placeholder="Сумма оплаты" required><div class="input-group-addon">сум</div></div></div>
            <div class="form-group"><button class="btn btn-success btn-lg" id="income_button">сформировать</button></div>
        </div>
    <?= Html::endForm(); ?>
</div>
