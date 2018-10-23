<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user \backend\models\User */

$this->registerJs(<<<SCRIPT
    Main.initPhoneFormatted();
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

    <div class="col-xs-12">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" <?= $searchValue ? '' : 'class="active"'; ?>><a href="#contract" aria-controls="contract" role="tab" data-toggle="tab">Номер договора</a></li>
            <li role="presentation" <?= $searchValue ? 'class="active"' : ''; ?>><a href="#phone" aria-controls="phone" role="tab" data-toggle="tab">Телефон</a></li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane <?= $searchValue ? '' : 'active'; ?>" id="contract">
                <form onsubmit="Money.findContract(); return false;">
                    <div class="input-group input-group-lg">
                        <input id="search_contract" class="form-control" placeholder="Номер договора" required pattern="\d+" <?= $searchValue ? '' : 'autofocus'; ?>>
                        <span class="input-group-btn">
                            <button class="btn btn-success">Искать</button>
                        </span>
                    </div>
                </form>
                <hr>
                <div id="contract_result_block"></div>
            </div>
            <div role="tabpanel" class="tab-pane <?= $searchValue ? 'active' : ''; ?>" id="phone">
                <div class="row">
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
                    <form onsubmit="return Money.completeIncome(this);">
                        <div class="col-xs-12 phone-search-result" id="pupils_block"></div>
                        <div class="col-xs-12 phone-search-result" id="groups_block"></div>
                        <div class="col-xs-12 hidden" id="payment_type_block">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-6">
                                            <button class="btn btn-default btn-lg full-width" type="button" id="payment-0" onclick="Money.setPayment(0);">Без скидки<br><small><span class="price"></span> в месяц</small></button>
                                        </div>
                                        <div class="col-xs-12 col-sm-6">
                                            <button class="btn btn-default btn-lg full-width" type="button" id="payment-1" onclick="Money.setPayment(1);">Со скидкой<br><small><span class="price"></span> в месяц</small></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label for="payment_date">Дата платежа</label>
                                <?= \dosamigos\datepicker\DatePicker::widget([
                                    'name' => 'payment_date',
                                    'value' => date('d.m.Y'),
                                    'options' => ['id' => 'payment_date', 'required' => true, 'pattern' => "\d{2}\.\d{2}\.\d{4}"],
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
                                <label for="payment_comment">Комментарий к платежу</label>
                                <input id="payment_comment" class="form-control">
                            </div>
                        </div>
                        <div class="col-xs-12">
                            <div class="form-group">
                                <label for="payment_contract">Номер договора</label>
                                <input id="payment_contract" class="form-control" required>
                            </div>
                        </div>
                        <div id="income_form" class="col-xs-12 hidden">
                            <div class="form-group"><div class="input-group"><input id="amount" name="payment_sum" type="number" min="1000" step="1000" class="form-control input-lg" placeholder="Сумма оплаты" required><div class="input-group-addon">сум</div></div></div>
                            <div class="form-group"><button class="btn btn-success btn-lg" id="income_button">внести</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>