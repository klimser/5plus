<?php

use yii\bootstrap\Html;

/* @var $this \frontend\components\extended\View */
/* @var $giftCardTypes \common\models\GiftCardType[] */

$this->registerJs(<<<SCRIPT
Main.initPhoneFormatted();
SCRIPT
);

$this->params['breadcrumbs'][] = 'Онлайн оплата'; ?>

<div class="row">
    <div class="col-xs-12 payment-panel">
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#pupil" aria-controls="pupil" role="tab" data-toggle="tab">Я уже занимаюсь</a></li>
            <li role="presentation"><a href="#customer" aria-controls="customer" role="tab" data-toggle="tab">Я - новый студент</a></li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="pupil">
                <?= Html::beginForm(\yii\helpers\Url::to(['payment/find']), 'post', ['onsubmit' => 'var gToken = grecaptcha.getResponse(); if (gToken.length === 0) return false;']); ?>
                <div class="form-group">
                    <label for="pupil-phone">Введите номер телефона студента или его(её) родителей</label>
                    <div class="input-group"><span class="input-group-addon">+998</span>
                        <input type="tel" name="phoneFormatted" id="pupil-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required>
                    </div>
                </div>
                <div class="pull-left max-width-100">
                    <?= \himiklab\yii2\recaptcha\ReCaptcha::widget(['name' => 'reCaptcha']) ?>
                </div>
                <div class="pull-right max-width-100">
                    <button class="btn btn-primary btn-lg">найти</button>
                </div>
                <div class="clearfix"></div>
                <?= Html::endForm(); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="customer">
                <?= Html::beginForm(\yii\helpers\Url::to(['payment/create-new']), 'post', ['onsubmit' => 'return Payment.completeNewPayment(this);']); ?>
                <div class="alert alert-info">
                    <b>Внимание!</b><br>
                    Указывайте свои данные корректно и полностью, они будут указаны в договоре между Вами и учебным центром.
                </div>
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group required">
                            <label for="giftcard-pupil-name">Фамилия, имя, отчество студента</label>
                            <input name="giftcard[pupil_name]" id="giftcard-pupil-name" class="form-control" maxlength="127" required>
                        </div>
                        <div class="form-group required">
                            <label for="giftcard-pupil-phone">Телефон студента</label>
                            <div class="input-group"><span class="input-group-addon">+998</span>
                                <input type="tel" name="giftcard[pupil_phone]" id="giftcard-pupil-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="giftcard-parents-name">Фамилия, имя, отчество родителей</label>
                            <input name="giftcard[parents_name]" id="giftcard-parents-name" class="form-control" maxlength="127">
                        </div>
                        <div class="form-group">
                            <label for="giftcard-parents-phone">Телефон родителей</label>
                            <div class="input-group"><span class="input-group-addon">+998</span>
                                <input type="tel" name="giftcard[parents_phone]" id="giftcard-parents-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group required">
                    <label for="giftcard-type">Предмет</label>
                    <select name="giftcard[type]" id="giftcard-type" class="form-control" required>
                        <?php foreach ($giftCardTypes as $giftCardType): ?>
                            <option value="<?= $giftCardType->id; ?>" data-amount="<?= $giftCardType->amount; ?>">
                                <?= $giftCardType->name . ' (' . $giftCardType->amount . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group required">
                    <label for="giftcard-email">E-mail для отправки квитанции об оплате</label>
                    <input type="email" name="giftcard[email]" id="giftcard-email" class="form-control" maxlength="255" required>
                </div>
                <div class="alert alert-info">
                    Квитанция об оплате со штрих-кодом будет выслана на указанный вами e-mail. Вам необходимо будет показать квитанцию на телефоне или в распечатанном виде Вашим заботливым менеджерам в администрации «Пять с Плюсом» для допуска к занятиям.<br>
                    Желаем вам хорошего настроения и шикарной учебы!
                </div>
                <div id="message_board"></div>
                <div class="text-right max-width-100">
                    <button class="btn btn-primary btn-lg pay_button">оплатить</button>
                </div>
                <?= Html::endForm(); ?>
            </div>
        </div>
    </div>
</div>