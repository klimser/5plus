<?php

/* @var $this yii\web\View */

$this->title = 'Личный кабинет';
?>
<?= \backend\components\DebtWidget::widget(['user' => Yii::$app->user->identity]); ?>
<div class="clearfix"></div>
<?php /*
<?= \backend\components\FuturePaymentsWidget::widget(['user' => Yii::$app->user->identity]); ?>
 */ ?>
<div class="row">
    <div class="hidden-xs col-sm-4">
        <div class='square-box'>
            <div class='square-content'><div><a href="<?= \yii\helpers\Url::to(['user/update']); ?>" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-user"></span> Профиль</a></div></div>
        </div>
    </div>
    <div class="hidden-xs col-sm-4">
        <div class='square-box'>
            <div class='square-content'><div><a href="<?= \yii\helpers\Url::to(['user/schedule']); ?>" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-list-alt"></span> Дневник</a></div></div>
        </div>
    </div>
    <div class="hidden-xs col-sm-4">
        <div class='square-box'>
            <div class='square-content'><div><a href="<?= \yii\helpers\Url::to(['user/money-history']); ?>" class="btn btn-default btn-lg"><span class="glyphicon glyphicon-usd"></span> История платежей</a></div></div>
        </div>
    </div>

    <a href="<?= \yii\helpers\Url::to(['user/update']); ?>" class="btn btn-default btn-lg col-xs-12 visible-xs"><span class="glyphicon glyphicon-user"></span> Профиль</a>
    <a href="<?= \yii\helpers\Url::to(['user/schedule']); ?>" class="btn btn-default btn-lg col-xs-12 visible-xs"><span class="glyphicon glyphicon-list-alt"></span> Расписание</a>
    <a href="<?= \yii\helpers\Url::to(['user/money-history']); ?>" class="btn btn-default btn-lg col-xs-12 visible-xs"><span class="glyphicon glyphicon-usd"></span> История платежей</a>
</div>
