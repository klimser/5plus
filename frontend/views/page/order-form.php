<?php

use common\components\helpers\Html;
use himiklab\yii2\recaptcha\ReCaptcha2;
use yii\helpers\Url;

/* @var $this \yii\web\View */

$this->registerJs('MainPage.init().done(MainPage.fillOrderSubjects);');

?>
<?= Html::beginForm(
    Url::to(['order/create']),
    'post',
    [
        'onsubmit' => 'fbq("track", "Lead", {content_name: $("#order-subject").find("option:selected").text()}); return MainPage.completeOrder(this);'
    ]
); ?>
    <div class="order_form_extra collapse"></div>
    <div class="order_form_body collapse show">
        <div class="form-group">
            <label for="order-name">Ваше имя</label>
            <input name="order[name]" id="order-name" class="form-control" required minlength="2" maxlength="50">
        </div>
        <div class="form-group">
            <label for="order-subject">Предмет</label>
            <select name="order[subject]" id="order-subject" class="form-control"></select>
        </div>
        <div class="form-group">
            <label for="order-phone">Ваш номер телефона</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">+998</span>
                </div>
                <input type="tel" name="order[phoneFormatted]" id="order-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required>
            </div>
        </div>
        <div class="form-group">
            <label for="order-comment">Дополнительные сведения, пожелания</label>
            <textarea name="order[user_comment]" id="order-comment" class="form-control" maxlength="255"></textarea>
        </div>
        <div class="row">
            <div class="col-12 col-md">
                <?= ReCaptcha2::widget(['name' => 'order[reCaptcha]']) ?>
            </div>
            <div class="col-12 col-md text-md-right pt-2">
                <button class="btn btn-primary">записаться</button>
            </div>
        </div>
    </div>
<?= Html::endForm(); ?>
