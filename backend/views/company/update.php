<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $company common\models\Company */

$this->title = $company->isNewRecord ? 'Новая компания' : $company->first_name;
$this->params['breadcrumbs'][] = ['label' => 'Компании', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="company-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="company-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($company, 'first_name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'second_name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'licence')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'head_name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'head_name_short')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'zip')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'city')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'address')->textInput(['maxlength' => true]) ?>
        
        <?= $form->field($company, 'address_school')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'phone')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'tin')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'bank_data')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'oked')->textInput(['maxlength' => true]) ?>

        <?= $form->field($company, 'mfo')->textInput(['maxlength' => true]) ?>

        <div class="form-group">
            <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
