<?php

use yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $user \common\models\User */
/* @var $companies \common\models\Company[] */
/* @var $groups \common\models\Group[] */

$searchValue = '';
if (isset($user)) {
    $this->registerJs(<<<SCRIPT
    Money.findPupils();
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
            <li role="presentation" <?= $searchValue ? '' : 'class="active"'; ?>><a href="#tab_contract" aria-controls="tab_contract" role="tab" data-toggle="tab">Номер договора</a></li>
            <li role="presentation" <?= $searchValue ? 'class="active"' : ''; ?>><a href="#tab_phone" aria-controls="tab_phone" role="tab" data-toggle="tab">Телефон</a></li>
            <li role="presentation"><a href="#tab_gift" aria-controls="tab_gidt" role="tab" data-toggle="tab">Предоплаченная карта</a></li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane <?= $searchValue ? '' : 'active'; ?>" id="tab_contract">
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
            <div role="tabpanel" class="tab-pane <?= $searchValue ? 'active' : ''; ?>" id="tab_phone">
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
                                            <button class="btn btn-default btn-lg full-width" type="button" id="payment-1" onclick="Money.setPayment(1);">Со скидкой<br><small><span class="price"></span> за 3 месяца</small></button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-12"><br>
                                            Занимается с <span class="big-font" id="date_start"></span><br>
                                            Оплачено до <span class="big-font" id="date_charge_till"></span><br>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label for="payment_comment">Комментарий к платежу</label>
                                <input id="payment_comment" class="form-control">
                            </div>
                        </div>
                        <div class="col-xs-12">
                            <?= Html::radioList(
                                'company_id',
                                null,
                                \yii\helpers\ArrayHelper::map($companies, 'id', 'second_name'),
                                ['class' => 'form-group', 'itemOptions' => ['required' => true]]
                            ); ?>
                        </div>
                        <div id="income_form" class="col-xs-12 hidden">
                            <div class="form-group">
                                <div class="input-group">
                                    <input id="amount" name="payment_sum" type="number" min="1000" step="1000" class="form-control input-lg" placeholder="Сумма оплаты" required>
                                    <div class="input-group-addon">сум</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button class="btn btn-success btn-lg" id="income_button">внести</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_gift">
                <form onsubmit="Money.findGiftCard(); return false;">
                    <div class="input-group input-group-lg">
                        <input id="search_gift_card" class="form-control" placeholder="Код с квитанции" required>
                        <span class="input-group-btn">
                            <button class="btn btn-success">Искать</button>
                        </span>
                    </div>
                </form>
                <hr>
                <div id="gift_card_messages"></div>
                <div id="gift_card_result_block" class="hidden">
                    <form onsubmit="return Money.completeGiftCard(this);">
                        <p>
                            <b>Предмет</b> <span id="gift_card_type"></span><br>
                            <b>Сумма</b> <span id="gift_card_amount"></span><br>
                        </p>
                        <input type="hidden" name="gift_card_id" id="gift_card_id" value="" required>
                        <input type="hidden" name="pupil[id]" id="existing_pupil_id">
                        <input type="hidden" name="group[existing]" id="existing_group_id">
                        <div class="form-group">
                            <label for="pupil_name">ФИО студента</label>
                            <input id="pupil_name" class="form-control" name="pupil[name]" required>
                        </div>
                        <div class="form-group">
                            <label for="pupil_phone">Телефон студента</label>
                            <div class="input-group">
                                <span class="input-group-addon">+998</span>
                                <input id="pupil_phone" class="form-control phone-formatted" name="pupil[phoneFormatted]" required maxlength="11" pattern="\d{2} \d{3}-\d{4}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="parents_name">ФИО родителей</label>
                            <input id="parents_name" class="form-control" name="parents[name]">
                        </div>
                        <div class="form-group">
                            <label for="parents_phone">Телефон родителей</label>
                            <div class="input-group">
                                <span class="input-group-addon">+998</span>
                                <input id="parents_phone" class="form-control phone-formatted" name="parents[phoneFormatted]" maxlength="11" pattern="\d{2} \d{3}-\d{4}">
                            </div>
                        </div>
                        <div id="predefined_groups" class="form-group"></div>
                        <div class="form-group">
                            <label for="new_group">Добавить в новую группу</label>
                            <div class="input-group">
                                <select id="new_group" name="group[id]" class="form-control">
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group->id; ?>"><?= $group->name; ?> (<?= $group->teacher->name; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="new_group">Дата начала занятий</label>
                            <div class="input-group">
                                <?= \dosamigos\datepicker\DatePicker::widget([
                                    'name' => 'group[date]',
                                    'value' => date('d.m.Y'),
                                    'language' => 'ru',
                                    'clientOptions' => [
                                        'autoclose' => true,
                                        'format' => 'dd.mm.yyyy',
                                        'startDate' => date('d.m.Y'),
                                        'language' => 'ru',
                                    ],
                                ]);?>
                            </div>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-success btn-lg" id="gift_button">внести</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
