<?php
use \yii\bootstrap\Html;
/* @var $this \yii\web\View */
/* @var $activeSubject string */
?>
<?= Html::beginForm('', 'post', ['id' => 'order_form', 'class' => 'row-fluid order_form', 'onsubmit' => 'Order.complete(); return false;']); ?>
    <div class="form_title form-group col-xs-12 text-center text-uppercase">
        Запишитесь на занятие
    </div>
    <div class="form-group col-xs-12">
        <?= Html::textInput('order[name]', null, ['class' => 'name form-control', 'placeholder' => 'Ваше имя', 'required' => true, 'min' => 2, 'maxlength' => 50, 'onfocus' => 'Order.hidePopover(this);']); ?>
    </div>
    <div class="form-group col-xs-12">
        <?= Html::dropDownList('order[subject]', $activeSubject, \common\models\OrderSubject::getAllSubjects(), ['class' => 'subject form-control']); ?>
    </div>
    <div class="dynamic_part hidden">
        <div class="form-group col-xs-12">
            <div>
                <?= Html::input('tel', 'order[phone]', null, ['class' => 'phone form-control', 'placeholder' => 'Номер телефона для связи', 'maxlength' => 25, 'pattern' => '\+998 \(\d{2}\) \d{3}-\d{2}-\d{2}', 'onfocus' => 'Order.hidePopover(this);']); ?>
            </div>
            <div>
                <?= Html::input('email', 'order[email]', null, ['class' => 'email form-control', 'placeholder' => 'E-mail для связи', 'maxlength' => 50, 'onfocus' => 'Order.hidePopover($(this).closest(\'.form-group\').find(\'.phone\'));']); ?>
            </div>
        </div>
        <div class="form-group col-xs-12">
            <?= Html::textarea('order[user_comment]', null, ['class' => 'comment form-control', 'placeholder' => 'Дополнительные сведения, пожелания', 'maxlength' => 255,]); ?>
        </div>
    </div>
    <div>
        <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2 text-uppercase']); ?>
    </div>
    <div class="clearfix"></div>
<?= Html::endForm(); ?>