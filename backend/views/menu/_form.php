<?php

use yii\bootstrap4\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $menu common\models\Menu */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="menu-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($menu, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($menu, 'title')->textInput(['maxlength' => true]) ?>

    <?= Html::submitButton($menu->isNewRecord ? 'Создать' : 'Сохранить', ['class' => $menu->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>

    <?php ActiveForm::end(); ?>

</div>
