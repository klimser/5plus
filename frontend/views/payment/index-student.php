<?php

use himiklab\yii2\recaptcha\ReCaptcha2;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */

$this->params['breadcrumbs'][] = 'Онлайн оплата для учащихся'; ?>

<div class="container">
    <div class="content-box payment-panel">
        <p>Вы можете <a href="https://payment.apelsin.uz/merchant?serviceId=498605820" target="_blank" class="btn apelsin_logo pay_button m-2">
            оплатить с помощью <span class="sr-only">Apelsin</span> <i class="ml-2"></i>
        </a></p>
        <p>ИЛИ</p>
        <?= Html::beginForm(Url::to(['payment/find']), 'post', ['onsubmit' => 'var gToken = grecaptcha.getResponse(); if (gToken.length === 0) return false;']); ?>
            <div class="form-group">
                <label for="student-phone">Введите свой номер телефона</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">+998</span>
                    </div>
                    <input type="tel" name="phoneFormatted" id="student-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" inputmode="numeric" autocomplete="tel-national" required autofocus>
                </div>
            </div>
            <div class="float-left mw-100">
                <?= ReCaptcha2::widget(['name' => 'reCaptcha']) ?>
            </div>
            <div class="float-right mw-100">
                <button class="btn btn-primary btn-lg">далее</button>
            </div>
            <div class="clearfix"></div>
        <?= Html::endForm(); ?>
    </div>
</div>
