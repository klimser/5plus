<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $quiz common\models\Quiz */
/* @var $form yii\widgets\ActiveForm */
/* @var $subjects \common\models\Subject[] */

?>

<div class="menu-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($quiz, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($quiz, 'subject_id')->dropDownList(\yii\helpers\ArrayHelper::map($subjects, 'id', 'name', 'subjectCategory.name')) ?>

    <div class="form-group">
        <?= Html::submitButton($quiz->isNewRecord ? 'Создать' : 'Сохранить', ['class' => $quiz->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
