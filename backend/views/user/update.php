<?php

use backend\components\DebtWidget;
use backend\components\UserComponent;
use common\models\User;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $isAdmin bool */
/* @var $editACL bool */
/* @var $authManager \yii\rbac\ManagerInterface*/
/* @var $existedParents User[] */
/* @var $parent User */

$this->registerJs(<<<SCRIPT
    Main.initPhoneFormatted();
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
                <?= DebtWidget::widget(['user' => $user]); ?>
            <?php else: ?>
                <?= DebtWidget::widget(['user' => Yii::$app->user->identity]); ?>
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>
    </div>

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-xs-12 <?php if ($isAdmin): ?>col-md-6<?php endif; ?>">
            <?= $form->field($user, 'name')->textInput(['maxlength' => true, 'disabled' => !$isAdmin]); ?>

            <?= $form->field($user, 'username')->textInput(['maxlength' => true]); ?>

            <?= $form->field($user, 'password')->passwordInput(['data' => ['id' => $user->id]]); ?>

            <?php if ($isAdmin): ?>
                <?= $form->field($user, 'note')->textarea(['maxlength' => true, 'htmlOptions' => ['rows' => 3]]); ?>

                <?= $form->field($user, 'phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                <?= $form->field($user, 'phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                <?php if ($editACL && ($user->role == User::ROLE_MANAGER || $user->role == User::ROLE_ROOT)): ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">Права</div>
                        <div class="panel-body">
                            <?php foreach (UserComponent::ACL_RULES as $ruleKey => $ruleLabel): ?>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="acl[<?= $ruleKey; ?>]" value="1" <?= $authManager->checkAccess($user->id, $ruleKey) ? 'checked' : ''; ?>>
                                        <?= $ruleLabel; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?= $form->field($user, 'phoneFull')->staticControl(); ?>
                <?php if ($user->phone2): ?>
                    <?= $form->field($user, 'phone2Full')->staticControl(); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($isAdmin && $user->role == User::ROLE_PUPIL && !$user->parent_id): ?>
            <div class="col-xs-12 col-md-6">
                <h2>Родители</h2>

                <div class="radio">
                    <label>
                        <input type="radio" name="parent_type" value="none" onclick="User.changeParentType()"> Студент уже взрослый
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="parent_type" value="exist" onclick="User.changeParentType()"> Есть брат (сестра), выбрать родителей из списка
                    </label>
                </div>
                <div id="parents_select" >
                    <?= Html::dropDownList('parent_exists', $parent->id, ArrayHelper::map($existedParents, 'id', 'name'), ['class' => 'form-control chosen']); ?>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="parent_type" value="new" checked onclick="User.changeParentType()"> Добавить родителей
                    </label>
                </div>

                <div id="parents_form">
                    <?= $form->field($parent, '[parent]name')->textInput(['maxlength' => true]); ?>

                    <?= $form->field($parent, '[parent]phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                    <?= $form->field($parent, '[parent]phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
