<?php

use himiklab\yii2\recaptcha\ReCaptcha;
use yii\bootstrap\Html;
use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */

$this->registerJs(<<<SCRIPT
Main.initPhoneFormatted();
SCRIPT
);

$this->params['breadcrumbs'][] = 'Онлайн оплата для учащихся'; ?>

<div class="row">
    <div class="col-xs-12 payment-panel">
        <?= Html::beginForm(Url::to(['payment/find']), 'post', ['onsubmit' => 'var gToken = grecaptcha.getResponse(); if (gToken.length === 0) return false;']); ?>
            <div class="form-group">
                <label for="pupil-phone">Введите номер телефона студента или его(её) родителей</label>
                <div class="input-group"><span class="input-group-addon">+998</span>
                    <input type="tel" name="phoneFormatted" id="pupil-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required>
                </div>
            </div>
            <div class="pull-left max-width-100">
                <?= ReCaptcha::widget(['name' => 'reCaptcha']) ?>
            </div>
            <div class="pull-right max-width-100">
                <button class="btn btn-primary btn-lg">найти</button>
            </div>
            <div class="clearfix"></div>
        <?= Html::endForm(); ?>
    </div>
</div>
