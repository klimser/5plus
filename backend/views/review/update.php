<?php

use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

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

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($review, 'name')->textInput(['required' => true, 'maxlength' => true]) ?>

        <?= $form->field($review, 'message')->textarea(['required' => true, 'maxlength' => true]) ?>

        <div class="form-group col-12">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
