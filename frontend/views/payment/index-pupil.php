<?php

use himiklab\yii2\recaptcha\ReCaptcha;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */

$this->params['breadcrumbs'][] = 'Онлайн оплата для учащихся'; ?>

<div class="container">
    <div class="content-box payment-panel">
        <?= Html::beginForm(Url::to(['payment/find']), 'post', ['onsubmit' => 'var gToken = grecaptcha.getResponse(); if (gToken.length === 0) return false;']); ?>
            <div class="form-group">
                <label for="pupil-phone">Введите номер телефона студента или его(её) родителей</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">+998</span>
                    </div>
                    <input type="tel" name="phoneFormatted" id="pupil-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required>
                </div>
            </div>
            <div class="float-left mw-100">
                <?= ReCaptcha::widget(['name' => 'reCaptcha']) ?>
            </div>
            <div class="float-right mw-100">
                <button class="btn btn-primary btn-lg">найти</button>
            </div>
            <div class="clearfix"></div>
        <?= Html::endForm(); ?>
    </div>
</div>
