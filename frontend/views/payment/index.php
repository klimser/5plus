<?php

use yii\bootstrap\Html;

/* @var $this \frontend\components\extended\View */
/* @var $page common\models\Page */
/* @var $webpage common\models\Webpage */
/* @var $feedback \common\models\Feedback */
/* @var $subjectCategoryCollection array */
/* @var $reviews array */

$this->registerJs(<<<SCRIPT
$("#pupil-phone").inputmask({"mask": "99 999-9999"});
SCRIPT
);

$this->params['breadcrumbs'][] =  'Онлайн оплата'; ?>

<?= Html::beginForm(\yii\helpers\Url::to(['payment/find']), 'post', ['onsubmit' => 'var gToken = grecaptcha.getResponse(); if (gToken.length === 0) return false;']); ?>
    <div class="form-group">
        <label for="pupil-phone">Введите номер телефона студента или его(её) родителей</label>
        <div class="input-group"><span class="input-group-addon">+998</span>
            <input type="tel" name="phoneFormatted" id="pupil-phone" class="form-control" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required>
        </div>
    </div>
    <?= \himiklab\yii2\recaptcha\ReCaptcha::widget(['name' => 'reCaptcha']) ?>

    <button class="btn btn-primary">найти</button>
<?= Html::endForm(); ?>
