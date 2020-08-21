<?php

use frontend\components\widgets\ReviewCarouselWidget;
use frontend\components\widgets\SubjectCarouselWidget;
use frontend\components\widgets\TeacherCarouselWidget;
use yii\bootstrap4\Html;
use \himiklab\yii2\recaptcha\ReCaptcha2;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $page common\models\Page */
/* @var $subjectCategoryCollection \common\models\SubjectCategory[] */
/* @var $webpage \common\models\Webpage */
/* @var $quizWebpage \common\models\Webpage */
/* @var $paymentWebpage \common\models\Webpage */

$this->registerJs('MainPage.init();');

//unset($this->params['h1']);

foreach ($subjectCategoryCollection as $subjectCategory): ?>
        <?= SubjectCarouselWidget::widget(['subjectCategory' => $subjectCategory]); ?>
<?php endforeach; ?>

<?= TeacherCarouselWidget::widget(); ?>

<?= ReviewCarouselWidget::widget(); ?>

<div id="order_form" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= Html::beginForm(
                    Url::to(['order/create']),
                    'post',
                    [
                        'onsubmit' => 'fbq("track", "Lead", {content_name: $("#order-subject").find("option:selected").text()}); return MainPage.completeOrder(this);'
                    ]
            ); ?>
            <div class="modal-header">
                <h4 class="modal-title">Записаться на курс</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
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
                    <?= ReCaptcha2::widget(['name' => 'order[reCaptcha]']) ?>
                </div>
                <div class="order_form_extra collapse"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                <button class="btn btn-primary">записаться</button>
            </div>
            <?= Html::endForm(); ?>
        </div>
    </div>
</div>
