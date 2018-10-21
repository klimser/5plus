<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $review common\models\Review */

$this->title = 'Обновить отзыв от ' . $review->name;
$this->params['breadcrumbs'][] = ['label' => 'Отзывы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $review->name;
?>
<div class="order-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <div class="help-block">Добавлен <?= $review->createDateString; ?></div>

    <div class="review-form">

        <?php $form = ActiveForm::begin(['options' => ['class' => 'row']]); ?>

        <?= $form->field($review, 'name', ['options' => ['class' => 'col-xs-12 col-sm-8 col-md-10']])->textInput(['required' => true, 'maxlength' => true]) ?>

        <?= $form->field($review, 'message', ['options' => ['class' => 'col-xs-12']])->textarea(['required' => true, 'maxlength' => true]) ?>

        <div class="form-group col-xs-12">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary col-xs-6 col-xs-offset-3 col-sm-4 col-sm-offset-0 col-md-2 col-lg-1']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
