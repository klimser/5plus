<?php

use common\components\DefaultValuesComponent;
use common\models\User;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $pupil User */
/* @var $activeTab string */
/* @var $incomeAllowed bool */
/* @var $contractAllowed bool */
/* @var $groupManagementAllowed bool */
/* @var $moveMoneyAllowed bool */
/* @var $welcomeLessonsAllowed bool */

$this->title = $pupil->name;
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view" id="user-view-<?= $pupil->id; ?>">
    <?php $form = ActiveForm::begin(['options' => ['onsubmit' => 'Dashboard.savePupil(this); return false;']]); ?>
    
    <div class="user-view-messages-place"></div>
    <?= $form->field($pupil, '[pupil]id', ['template' => '{input}', 'options' => ['class' => []]])->hiddenInput(); ?>

    <div class="row">
        <div class="col-12 col-md-6 pupil-info-block">
            <h2>
                <small class="fas fa-pencil-alt point" onclick="Dashboard.showEditForm('pupil', this);"></small>
                <?= Html::encode($this->title) ?>
                <small><small>
                    <?php if ($pupil->individual): ?>
                        <span class="badge badge-success">Физ. лицо</span>
                    <?php else: ?>
                        <span class="badge badge-info">Юр. лицо</span>
                    <?php endif; ?>
                </small></small>
            </h2>
            <div class="pupil-view-block collapse show">
                <?= $form->field($pupil, 'phoneFull')->staticControl(); ?>
    
                <?php if ($pupil->phone2): ?>
                    <?= $form->field($pupil, 'phone2Full')->staticControl(); ?>
                <?php endif; ?>
    
                <?php if ($pupil->note): ?>
                    <?= $form->field($pupil, 'note')->staticControl(); ?>
                <?php endif; ?>
            </div>
            <div class="pupil-edit-block collapse">
                <?= $form->field($pupil, '[pupil]name')->textInput(['maxlength' => true, 'required' => true, 'disabled' => true]); ?>

                <?= $form->field($pupil, '[pupil]note')->textarea(['maxlength' => true, 'disabled' => true, 'htmlOptions' => ['rows' => 3]]); ?>

                <?= $form->field($pupil, '[pupil]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'disabled' => true, 'required' => true, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>

                <?= $form->field($pupil, '[pupil]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'disabled' => true, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>
            </div>
        </div>

        <hr class="d-md-none">

        <div class="col-12 col-md-6 parent-info-block">
            <h2>
                <small class="fas fa-pencil-alt point" onclick="Dashboard.showEditForm('parent', this);"></small>
                <?= $pupil->individual ? 'Родители' : 'Компания'; ?>
            </h2>

            <div class="parent-view-block collapse show">
                <?php if (!$pupil->parent_id): ?>
                    <span class="label label-default">Студент уже взрослый</span>
                <?php else: ?>
                    <?= $form->field($pupil->parent, 'name')->staticControl(); ?>
    
                    <?= $form->field($pupil->parent, 'phoneFull')->staticControl(); ?>

                    <?php if ($pupil->parent->phone2): ?>
                        <?= $form->field($pupil->parent, 'phone2Full')->staticControl(); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="parent-edit-block collapse">
                <?php if (!$pupil->parent_id): ?>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input class="form-check-input" type="radio" name="parent_type" value="none" onclick="Dashboard.changeParentType(this)" checked>
                            Студент уже взрослый
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input class="form-check-input" type="radio" name="parent_type" value="exist" onclick="Dashboard.changeParentType(this)">
                            Есть брат (сестра), выбрать родителей
                        </label>
                    </div>
                    <div class="parent-edit-option parent-edit-exist collapse">
                        <input type="hidden" class="autocomplete-user-id" name="User[parent][id]">
                        <input class="autocomplete-user form-control" placeholder="начните печатать фамилию или имя" required disabled data-role="<?= $pupil->individual ? User::ROLE_PARENTS : User::ROLE_COMPANY; ?>">
                    </div>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input class="form-check-input" type="radio" name="parent_type" value="new" onclick="Dashboard.changeParentType(this)">
                            Родители
                        </label>
                    </div>
                <?php endif; ?>

                <div class="parent-edit-option parent-edit-new collapse <?= $pupil->parent_id ? ' show ' : ''; ?>">
                    <?php $parent = $pupil->parent_id ? $pupil->parent : new User(); ?>
                    <?= $form->field($parent, '[parent]name')->textInput(['maxlength' => true, 'required' => true, 'disabled' => !$pupil->parent_id]); ?>

                    <?= $form->field($parent, '[parent]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'required' => true, 'disabled' => !$pupil->parent_id, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                    <?= $form->field($parent, '[parent]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'disabled' => !$pupil->parent_id, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
        $getTabLi = function(string $tabId, string $title) use ($pupil, $activeTab) {
            $elemId = $tabId . '-tab-' . $pupil->id;
            return '<li class="nav-item">'
                . '<a class="nav-link ' . ($activeTab === $tabId ? ' active ' : '') . '" href="#' . $elemId . '" aria-controls="' . $elemId . '" role="tab" data-toggle="tab">' . $title . '</a></li>';
        };
        
        $getTabDiv = function(string $tabId) use ($pupil, $activeTab) {
            return '<div role="tabpanel" class="tab-pane fade ' . ($activeTab === $tabId ? ' active show ' : '') . '" id="' . $tabId . '-tab-' . $pupil->id . '">';
        };
    ?>

    <div class="row">
        <div class="col-12 user-view-tabs">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <?= $getTabLi('consultation', 'консультации'); ?>
                <?php if ($welcomeLessonsAllowed): ?>
                    <?= $getTabLi('welcome_lesson', 'пробные уроки'); ?>
                <?php endif; ?>
                <?= $getTabLi('group', 'группы'); ?>
                <?= $getTabLi('contract', 'договоры'); ?>
                <?= $getTabLi('payment', 'платежи'); ?>
            </ul>

            <div class="tab-content">
                <?= $getTabDiv('consultation'); ?>
                    <?= $this->render('_view_consultation', ['pupil' => $pupil]); ?>
                </div>
                <?php if ($welcomeLessonsAllowed): ?>
                    <?= $getTabDiv('welcome_lesson'); ?>
                        <?= $this->render('_view_welcome_lesson', ['pupil' => $pupil]); ?>
                    </div>
                <?php endif; ?>
                <?= $getTabDiv('group'); ?>
                    <?= $this->render('_view_group', [
                        'pupil' => $pupil,
                        'contractAllowed' => $contractAllowed,
                        'incomeAllowed' => $incomeAllowed,
                        'groupManagementAllowed' => $groupManagementAllowed,
                        'moveMoneyAllowed' => $moveMoneyAllowed,
                    ]); ?>
                </div>
                <?= $getTabDiv('contract'); ?>
                    <?= $this->render('_view_contract', ['pupil' => $pupil]); ?>
                </div>
                <?= $getTabDiv('payment'); ?>
                    <?= $this->render('_view_payment', ['pupil' => $pupil]); ?>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="form-group col-12 text-right">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary']); ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
