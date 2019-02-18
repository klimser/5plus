<?php

use yii\bootstrap\Html;

/* @var $this \frontend\components\extended\View */

$this->registerJs(<<<SCRIPT
$("#pupil-phone").inputmask({"mask": "99 999-9999"});
SCRIPT
);

$this->params['breadcrumbs'][] = 'Онлайн оплата'; ?>

<?= Html::beginForm(\yii\helpers\Url::to(['payment/find']), 'post', ['onsubmit' => 'var gToken = grecaptcha.getResponse(); if (gToken.length === 0) return false;']); ?>
    <div class="form-group">
        <label for="pupil-phone">Введите номер телефона студента или его(её) родителей</label>
        <div class="input-group"><span class="input-group-addon">+998</span>
            <input type="tel" name="phoneFormatted" id="pupil-phone" class="form-control" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required>
        </div>
    </div>
    <div class="pull-left max-width-100">
        <?= \himiklab\yii2\recaptcha\ReCaptcha::widget(['name' => 'reCaptcha']) ?>
    </div>
    <div class="pull-right max-width-100">
        <button class="btn btn-primary btn-lg">найти</button>
    </div>
<?= Html::endForm(); ?>
