<?php

use himiklab\yii2\recaptcha\ReCaptcha2;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
?>

<?= Html::beginForm(Url::to(['feedback/create']), 'post', ['id' => 'feedback_form', 'onsubmit' => 'return Feedback.complete(this);']); ?>
<div id="feedback_form_body">
    <div class="form-row">
        <div class="form-group col-md-6">
            <input name="feedback[name]" class="form-control form-control-lg" placeholder="Ваше имя" required maxlength="50">
        </div>
        <div class="form-group col-md-6">
            <input name="feedback[contact]" class="form-control form-control-lg" placeholder="Контактная информация" required maxlength="255">
        </div>
    </div>
    <div class="form-group">
        <textarea class="form-control form-control-lg" name="feedback[message]" rows="3" placeholder="Сообщение" required></textarea>
    </div>
    <div class="row justify-content-center">
        <?= ReCaptcha2::widget(['name' => 'feedback[reCaptcha]']) ?>
        <button type="submit" class="btn btn-default">Отправить <span class="fas fa-angle-double-right"></span></button>
    </div>

    <div id="feedback_form_extra"></div>
    <div id="feedback_form_complete" class="d-none">
        <span class="fas fa-check"></span> Ваше сообщение отправлено. Мы ответим вам в ближайшее время.<br>
        <button class="btn btn-default feedback_button" onclick="Feedback.resetForm();">Написать ещё сообщение.</button>
    </div>
</div>
<?= Html::endForm(); ?>
