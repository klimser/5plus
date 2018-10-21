<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $user backend\models\User */
/* @var $isAdmin bool */

$this->registerJs(<<<SCRIPT
    User.init();
SCRIPT
);

/** @var \yii\web\User $currentUser */
$currentUser = Yii::$app->user;

$this->title = 'Профиль' . ($isAdmin ? ' ' . $user->name : '');
if ($isAdmin) $this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-update">
    <div class="row">
        <div class="col-xs-12">
            <h1 class="pull-left no-margin-top"><?= Html::encode($this->title) ?></h1>
            <?php if ($isAdmin): ?>
                <?= \backend\components\DebtWidget::widget(['user' => $user]); ?>
            <?php else: ?>
                <?= \backend\components\DebtWidget::widget(['user' => Yii::$app->user->identity]); ?>
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="user-form">
        <?php $form = ActiveForm::begin(); ?>

        <?php if ($isAdmin): ?>
            <?= $form->field($user, 'name')->textInput(['maxlength' => true]); ?>
        <?php else: ?>
            <?= $form->field($user, 'name')->textInput(['maxlength' => true, 'disabled' => true]); ?>
        <?php endif; ?>

        <?= $form->field($user, 'username')->textInput(['maxlength' => true]); ?>

        <?= $form->field($user, 'password')->passwordInput(['data' => ['id' => $user->id]]); ?>

        <?php if ($isAdmin): ?>
            <?= $form->field($user, 'phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

            <?= $form->field($user, 'phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
        <?php else: ?>
            <?= $form->field($user, 'phoneFull')->staticControl(); ?>
            <?php if ($user->phone2): ?>
                <?= $form->field($user, 'phone2Full')->staticControl(); ?>
            <?php endif; ?>
        <?php endif; ?>

        <div class="form-group">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>