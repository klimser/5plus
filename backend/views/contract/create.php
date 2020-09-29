<?php

use yii\bootstrap4\Html;

/* @var $this yii\web\View */
/* @var $user \common\models\User */
/* @var $companies \common\models\Company[] */

$this->registerJs(<<<SCRIPT
    Contract.init();
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
<h1><?= Html::encode($this->title) ?></h1>

<div id="messages_place"></div>

<div class="row">
    <div class="col-12 my-3">
        <form onsubmit="Money.findPupils(); return false;">
            <div class="input-group input-group-lg">
                <div class="input-group-prepend">
                    <span class="input-group-text">+998</span>
                </div>
                <input id="search_phone" class="form-control phone-formatted" placeholder="Телефон ученика или родителей" required minlength="11" maxlength="11" pattern="\d{2} \d{3}-\d{4}" value="<?= $searchValue; ?>" autofocus>
                <div class="input-group-append">
                    <button class="btn btn-success">Искать</button>
                </div>
            </div>
        </form>
    </div>
    
    <?= Html::beginForm('', 'post', ['class' => 'col-12', 'onsubmit' => 'return Contract.complete(this);']); ?>
    <input type="hidden" name="user_id" id="user_input">
    <input type="hidden" name="group_id" id="group_input">
    <input type="hidden" name="discount" id="discount_input">
    <div class="row">
        <div class="col-12 collapse mb-3" id="pupils_block">
            <div class="card">
                <div class="card-header">Студенты</div>
                <div class="card-body" id="pupils_result"></div>
            </div>
        </div>
    
        <div class="col-12 collapse mb-3" id="groups_block">
            <div class="card">
                <div class="card-header">Группы</div>
                <div class="card-body" id="groups_result"></div>
            </div>
        </div>
    
        <div class="col-12 collapse mb-3" id="payment_type_block">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <button class="btn btn-outline-secondary btn-lg btn-block mb-2" type="button" id="payment-0" onclick="Contract.setPayment(0);">Без скидки<br><small><span class="price"></span> в месяц</small></button>
                        </div>
                        <div class="col-12 col-md-6">
                            <button class="btn btn-outline-secondary btn-lg btn-block mb-2" type="button" id="payment-1" onclick="Contract.setPayment(1);">Со скидкой<br><small><span class="price"></span> за 4 месяца</small></button>
                        </div>
                    </div>
                    <div class="row collapse" id="group_dates">
                        <div class="col-12">
                            Занимается с <span class="big-font" id="date_start"></span><br>
                            Оплачено до <span class="big-font" id="date_charge_till"></span><br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 collapse mb-3" id="income_form">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            <input id="amount" name="amount" type="number" min="1000" step="1000" class="form-control input-lg" placeholder="Сумма оплаты" required>
                            <div class="input-group-append">
                                <div class="input-group-text">сум</div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-success btn-lg" id="income_button">сформировать</button>
                </div>
            </div>
        </div>
    </div>
    <?= Html::endForm(); ?>
</div>
