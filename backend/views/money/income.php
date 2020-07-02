<?php

use common\components\DefaultValuesComponent;
use yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $user \common\models\User */
/* @var $companies \common\models\Company[] */
/* @var $groups \common\models\Group[] */

$searchValue = '';
$this->registerJs(<<<SCRIPT
    Money.init();
SCRIPT
);
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
<div class="money-income">
    <h1><?= Html::encode($this->title) ?></h1>

    <div id="messages_place"></div>

    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="nav-item">
            <a class="nav-link <?= $searchValue ? '' : ' active '; ?>" href="#tab_contract" aria-controls="tab_contract" role="tab" data-toggle="tab">Номер договора</a>
        </li>
        <li role="presentation" class="nav-item">
            <a class="nav-link <?= $searchValue ? ' active ' : ''; ?>" href="#tab_phone" aria-controls="tab_phone" role="tab" data-toggle="tab">Телефон</a>
        </li>
        <li role="presentation" class="nav-item">
            <a class="nav-link" href="#tab_gift" aria-controls="tab_gift" role="tab" data-toggle="tab">Предоплаченная карта</a>
        </li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade <?= $searchValue ? '' : ' show active '; ?>" id="tab_contract">
            <form onsubmit="Money.findContract(); return false;" class="mt-3">
                <div class="input-group input-group-lg">
                    <input id="search_contract" class="form-control" placeholder="Номер договора" required pattern="\d+" <?= $searchValue ? '' : 'autofocus'; ?> autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-success"><span class="fas fa-search"></span></button>
                    </div>
                </div>
            </form>
            <div id="contract_result_block" class="mt-3"></div>
        </div>
        <div role="tabpanel" class="tab-pane fade <?= $searchValue ? ' show active ' : ''; ?>" id="tab_phone">
            <form onsubmit="Money.findPupils(); return false;" class="mt-3">
                <div class="input-group input-group-lg">
                    <div class="input-group-prepend">
                        <span class="input-group-text">+998</span>
                    </div>
                    <input id="search_phone" class="form-control phone-formatted" placeholder="Телефон ученика или родителей" required minlength="11" maxlength="11" pattern="\d{2} \d{3}-\d{4}" value="<?= $searchValue; ?>" <?= $searchValue ? 'autofocus' : ''; ?>>
                    <div class="input-group-append">
                        <button class="btn btn-success"><span class="fas fa-search"></span></button>
                    </div>
                </div>
            </form>
            <form class="row mt-3" onsubmit="return Money.completeIncome(this);">
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
                                    <button class="btn btn-outline-dark btn-lg btn-block mb-2" type="button" id="payment-0" onclick="Money.setPayment(0);">Без скидки<br><small><span class="price"></span> в месяц</small></button>
                                </div>
                                <div class="col-12 col-md-6">
                                    <button class="btn btn-outline-dark btn-lg btn-block mb-2" type="button" id="payment-1" onclick="Money.setPayment(1);">Со скидкой<br><small><span class="price"></span> за 3 месяца</small></button>
                                </div>
                            </div>
                            <div class="row">
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
                                    <input id="amount" name="payment_sum" type="number" min="1000" step="1000" class="form-control input-lg" placeholder="Сумма оплаты" required>
                                    <div class="input-group-append"><div class="input-group-text">сум</div></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="payment_comment">Комментарий к платежу</label>
                                <input id="payment_comment" class="form-control">
                            </div>
                            <button class="btn btn-primary btn-lg" id="income_button">внести</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="tab_gift">
            <form onsubmit="Money.findGiftCard(); return false;" class="mt-3">
                <div class="input-group input-group-lg">
                    <input id="search_gift_card" class="form-control" placeholder="Код с квитанции" required>
                    <div class="input-group-append">
                        <button class="btn btn-success"><span class="fas fa-search"></span></button>
                    </div>
                </div>
            </form>
            <hr>
            <div id="gift_card_messages"></div>
            <div id="gift_card_result_block" class="collapse">
                <form onsubmit="Money.completeGiftCard(this); return false;">
                    <p>
                        <b>Предмет</b> <span id="gift_card_type"></span><br>
                        <b>Сумма</b> <span id="gift_card_amount"></span><br>
                    </p>
                    <input type="hidden" name="gift_card_id" id="gift_card_id" value="" required>
                    <input type="hidden" name="pupil[id]" id="existing_pupil_id">
                    <input type="hidden" name="group[existing]" id="existing_group_id">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="pupil_name">ФИО студента</label>
                                <input id="pupil_name" class="form-control" name="pupil[name]" required>
                            </div>
                            <div class="form-group">
                                <label for="pupil_phone">Телефон студента</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+998</span>
                                    </div>
                                    <input id="pupil_phone" class="form-control phone-formatted" name="pupil[phoneFormatted]" required maxlength="11" pattern="\d{2} \d{3}-\d{4}">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label for="parents_name">ФИО родителей</label>
                                <input id="parents_name" class="form-control" name="parents[name]">
                            </div>
                            <div class="form-group">
                                <label for="parents_phone">Телефон родителей</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+998</span>
                                    </div>
                                    <input id="parents_phone" class="form-control phone-formatted" name="parents[phoneFormatted]" maxlength="11" pattern="\d{2} \d{3}-\d{4}">
                                </div>
                            </div>
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
                            <?= DatePicker::widget(
                                ArrayHelper::merge(DefaultValuesComponent::getDatePickerSettings(),
                                [
                                'name' => 'group[date]',
                                'value' => date('d.m.Y'),
                                'options' => [
                                    'required' => true,
                                    'autocomplete' => 'off',
                                ],
                            ]));?>
                        </div>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary btn-lg" id="gift_button">внести</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
