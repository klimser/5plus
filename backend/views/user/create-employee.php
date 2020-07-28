<?php

use common\components\DefaultValuesComponent;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $user common\models\User */

$this->title = 'Добавить сотрудника';
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Новый сотрудник';
?>
<h1><?= Html::encode($this->title); ?></h1>

<?php $form = ActiveForm::begin(); ?>

<?= $form->field($user, 'name')->textInput(['maxlength' => true]); ?>

<?= $form->field($user, 'username')->textInput(['maxlength' => true, 'required' => true]); ?>

<?= $form->field($user, 'password')->passwordInput(['required' => $user->isNewRecord, 'data' => ['id' => $user->id]]); ?>

<?= $form->field($user, 'role')->dropDownList([User::ROLE_ROOT => 'Администратор', User::ROLE_MANAGER => 'Менеджер']); ?>

<?= $form->field($user, 'phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

<?= $form->field($user, 'phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

<div class="form-group text-right">
    <?= Html::submitButton('Добавить', ['class' => 'btn btn-primary']); ?>
</div>

<?php ActiveForm::end(); ?>
