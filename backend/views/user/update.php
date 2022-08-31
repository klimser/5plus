<?php

use backend\components\DebtWidget;
use backend\components\UserComponent;
use common\components\DefaultValuesComponent;
use common\models\User;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $isAdmin bool */
/* @var $editACL bool */
/* @var $authManager \yii\rbac\ManagerInterface*/
/* @var $parent User */

/** @var \yii\web\User $currentUser */
$currentUser = Yii::$app->user;

$this->title = 'Профиль' . ($isAdmin ? ' ' . $user->name : '');
if ($isAdmin) $this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs('User.init(true);');
?>
<div class="user-update">
    <h1 class="float-left mt-0"><?= Html::encode($this->title) ?></h1>
    <?php if ($isAdmin): ?>
        <?= DebtWidget::widget(['user' => $user]); ?>
    <?php else: ?>
        <?= DebtWidget::widget(['user' => Yii::$app->user->identity]); ?>
    <?php endif; ?>
    <div class="clearfix"></div>

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-12 <?php if ($isAdmin): ?>col-md-6<?php endif; ?>">
            <?= $form->field($user, 'name')->textInput(['maxlength' => true, 'disabled' => !$isAdmin]); ?>

            <?= $form->field($user, 'username')->textInput(['maxlength' => true]); ?>

            <?= $form->field($user, 'password')->passwordInput(['data' => ['id' => $user->id]]); ?>

            <?php if ($isAdmin): ?>
                <?= $form->field($user, 'note')->textarea(['maxlength' => true, 'htmlOptions' => ['rows' => 3]]); ?>

                <?= $form->field($user, 'phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                <?= $form->field($user, 'phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                <?php if ($editACL && ($user->role == User::ROLE_MANAGER || $user->role == User::ROLE_ROOT || $user->role == User::ROLE_TEACHER)): ?>
                    <div class="card form-group">
                        <div class="card-header">Права</div>
                        <div class="card-body">
                            <?php foreach (($user->role == User::ROLE_TEACHER ? UserComponent::ACL_TEACHER_RULES : UserComponent::ACL_RULES) as $ruleKey => $ruleLabel): ?>
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="checkbox" name="acl[<?= $ruleKey; ?>]" value="1" <?= $authManager->checkAccess($user->id, $ruleKey) ? 'checked' : ''; ?>>
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

        <?php if ($isAdmin && $user->role == User::ROLE_STUDENT): ?>
            <div class="col-12 col-md-6">
                <h2>Родители</h2>
                
                <?php if (!$user->parent_id): ?>
                    <div class="parent-edit-block">
                        <div class="form-check">
                            <label class="form-check-label">
                                <input class="form-check-input" type="radio" name="parent_type" value="none" onclick="Dashboard.changeParentType(this)">
                                Студент уже взрослый
                            </label>
                        </div>
                        <div class="form-check">
                            <label class="form-check-label">
                                <input class="form-check-input" type="radio" name="parent_type" value="exist" onclick="Dashboard.changeParentType(this)">
                                Есть брат (сестра), выбрать родителей из списка
                            </label>
                        </div>
                        <div class="parent-edit-option parent-edit-exist collapse">
                            <input type="hidden" class="autocomplete-user-id" name="User[parent][id]">
                            <input class="autocomplete-user form-control" placeholder="начните печатать фамилию или имя" required disabled data-role="<?= $user->individual ? User::ROLE_PARENTS : User::ROLE_COMPANY; ?>">
                        </div>
                        <div class="form-check">
                            <label class="form-check-label">
                                <input class="form-check-input" type="radio" name="parent_type" value="new" onclick="Dashboard.changeParentType(this)" checked>
                                Добавить родителей
                            </label>
                        </div>
        
                        <div class="parent-edit-option parent-edit-new collapse show">
                            <?= $form->field($parent, '[parent]name')->textInput(['maxlength' => true]); ?>
        
                            <?= $form->field($parent, '[parent]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                                ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'required' => true]); ?>
        
                            <?= $form->field($parent, '[parent]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                                ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?= $form->field($user->parent, '[parent]name')->staticControl(); ?>

                    <?= $form->field($user->parent, '[parent]phoneFull')->staticControl(); ?>

                    <?= $form->field($user->parent, '[parent]phone2Full')->staticControl(); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
