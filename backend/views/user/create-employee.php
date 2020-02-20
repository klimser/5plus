<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $user common\models\User */

$this->title = 'Добавить сотрудника';
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Новый сотрудник';
?>
<div class="user-create">
    <h1><?= Html::encode($this->title); ?></h1>

    <div class="user-form">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($user, 'name')->textInput(['maxlength' => true]); ?>

        <?= $form->field($user, 'username')->textInput(['maxlength' => true, 'required' => true]); ?>

        <?= $form->field($user, 'password')->passwordInput(['required' => $user->isNewRecord, 'data' => ['id' => $user->id]]); ?>

        <?= $form->field($user, 'role')->dropDownList([User::ROLE_ROOT => 'Администратор', User::ROLE_MANAGER => 'Менеджер']); ?>

        <?= $form->field($user, 'phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
            ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

        <?= $form->field($user, 'phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
            ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

        <div class="form-group">
            <?= Html::submitButton('Добавить', ['class' => 'btn btn-primary pull-right']); ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
