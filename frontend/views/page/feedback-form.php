<?php

use yii\helpers\Html;

/* @var $this \yii\web\View */
?>

<?= Html::beginForm(\yii\helpers\Url::to(['feedback/create']), 'post', ['id' => 'feedback_form', 'onsubmit' => 'return Feedback.complete(this);']); ?>
    <div id="feedback_form_body">
        <div class="row">
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    <input class="form-control" name="feedback[name]" placeholder="Ваше имя" required maxlength="50">
                </div>
                <div class="form-group">
                    <input class="form-control" name="feedback[contact]" placeholder="Контактная информация" required maxlength="255">
                </div>
                <div class="form-group">
                    <textarea class="form-control" name="feedback[message]" placeholder="Сообщение" required></textarea>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    <?= \himiklab\yii2\recaptcha\ReCaptcha::widget(['name' => 'feedback[reCaptcha]']) ?>
                </div>
                <div class="form-group">
                    <?= Html::submitButton('отправить', ['class' => 'btn btn-primary feedback_button']) ?>
                </div>
            </div>
        </div>
    </div>
    <div id="feedback_form_extra"></div>
    <div id="feedback_form_complete" class="hidden">
        <span class="fas fa-check"></span> Ваше сообщение отправлено. Мы ответим вам в ближайшее время.<br>
        <button class="btn btn-default feedback_button" onclick="Feedback.resetForm();">Написать ещё сообщение.</button>
    </div>
<?= Html::endForm(); ?>