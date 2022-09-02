<?php

use common\components\DefaultValuesComponent;
use common\models\User;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $student User */
/* @var $activeTab string */
/* @var $incomeAllowed bool */
/* @var $debtAllowed bool */
/* @var $contractAllowed bool */
/* @var $courseManagementAllowed bool */
/* @var $moveMoneyAllowed bool */
/* @var $welcomeLessonsAllowed bool */

$this->title = $student->name;
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view" id="user-view-<?= $student->id; ?>">
    <?php $form = ActiveForm::begin(['options' => ['onsubmit' => 'Dashboard.saveStudent(this); return false;']]); ?>
    
    <div class="user-view-messages-place"></div>
    <?= $form->field($student, '[student]id', ['template' => '{input}', 'options' => ['class' => []]])->hiddenInput(); ?>

    <div class="row">
        <div class="col-12 col-md-6 student-info-block">
            <h2>
                <small class="fas fa-pencil-alt point" onclick="Dashboard.showEditForm('student', this);"></small>
                <?= Html::encode($this->title) ?>
                <small><small>
                    <?php if ($student->individual): ?>
                        <span class="badge badge-success">Физ. лицо</span>
                    <?php else: ?>
                        <span class="badge badge-info">Юр. лицо</span>
                    <?php endif; ?>
                </small></small>
            </h2>
            <div class="student-view-block collapse show">
                <?= $form->field($student, 'phoneFull')->staticControl(); ?>
    
                <?php if ($student->phone2): ?>
                    <?= $form->field($student, 'phone2Full')->staticControl(); ?>
                <?php endif; ?>
    
                <?php if ($student->note): ?>
                    <?= $form->field($student, 'note')->staticControl(); ?>
                <?php endif; ?>
            </div>
            <div class="student-edit-block collapse">
                <?= $form->field($student, '[student]name')->textInput(['maxlength' => true, 'required' => true, 'disabled' => true]); ?>

                <?= $form->field($student, '[student]note')->textarea(['maxlength' => true, 'disabled' => true, 'htmlOptions' => ['rows' => 3]]); ?>

                <?= $form->field($student, '[student]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'disabled' => true, 'required' => true, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>

                <?= $form->field($student, '[student]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'disabled' => true, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>
            </div>
        </div>

        <hr class="d-md-none">

        <div class="col-12 col-md-6 parent-info-block">
            <h2>
                <small class="fas fa-pencil-alt point" onclick="Dashboard.showEditForm('parent', this);"></small>
                <?= $student->individual ? 'Родители' : 'Компания'; ?>
            </h2>

            <div class="parent-view-block collapse show">
                <?php if (!$student->parent_id): ?>
                    <span class="label label-default">Студент уже взрослый</span>
                <?php else: ?>
                    <?= $form->field($student->parent, 'name')->staticControl(); ?>
    
                    <?= $form->field($student->parent, 'phoneFull')->staticControl(); ?>

                    <?php if ($student->parent->phone2): ?>
                        <?= $form->field($student->parent, 'phone2Full')->staticControl(); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="parent-edit-block collapse">
                <?php if (!$student->parent_id): ?>
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
                        <input class="autocomplete-user form-control" placeholder="начните печатать фамилию или имя" required disabled data-role="<?= $student->individual ? User::ROLE_PARENTS : User::ROLE_COMPANY; ?>">
                    </div>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input class="form-check-input" type="radio" name="parent_type" value="new" onclick="Dashboard.changeParentType(this)">
                            Родители
                        </label>
                    </div>
                <?php endif; ?>

                <div class="parent-edit-option parent-edit-new collapse <?= $student->parent_id ? ' show ' : ''; ?>">
                    <?php $parent = $student->parent_id ? $student->parent : new User(); ?>
                    <?= $form->field($parent, '[parent]name')->textInput(['maxlength' => true, 'required' => true, 'disabled' => !$student->parent_id]); ?>

                    <?= $form->field($parent, '[parent]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'required' => true, 'disabled' => !$student->parent_id, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                    <?= $form->field($parent, '[parent]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'disabled' => !$student->parent_id, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
        $getTabLi = function(string $tabId, string $title) use ($student, $activeTab) {
            $elemId = $tabId . '-tab-' . $student->id;
            return '<li class="nav-item">'
                . '<a class="nav-link ' . ($activeTab === $tabId ? ' active ' : '') . '" href="#' . $elemId . '" aria-controls="' . $elemId . '" role="tab" data-toggle="tab">' . $title . '</a></li>';
        };
        
        $getTabDiv = function(string $tabId) use ($student, $activeTab) {
            return '<div role="tabpanel" class="tab-pane fade ' . ($activeTab === $tabId ? ' active show ' : '') . '" id="' . $tabId . '-tab-' . $student->id . '">';
        };
    ?>

    <div class="row">
        <div class="col-12 user-view-tabs">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <?= $getTabLi('consultation', 'консультации'); ?>
                <?php if ($welcomeLessonsAllowed): ?>
                    <?= $getTabLi('welcome_lesson', 'пробные уроки'); ?>
                <?php endif; ?>
                <?= $getTabLi('course', 'группы'); ?>
                <?= $getTabLi('contract', 'договоры'); ?>
                <?= $getTabLi('payment', 'платежи'); ?>
            </ul>

            <div class="tab-content">
                <?= $getTabDiv('consultation'); ?>
                    <?= $this->render('_view_consultation', ['student' => $student]); ?>
                </div>
                <?php if ($welcomeLessonsAllowed): ?>
                    <?= $getTabDiv('welcome_lesson'); ?>
                        <?= $this->render('_view_welcome_lesson', ['student' => $student]); ?>
                    </div>
                <?php endif; ?>
                <?= $getTabDiv('course'); ?>
                    <?= $this->render('_view_course', [
                        'student' => $student,
                        'contractAllowed' => $contractAllowed,
                        'incomeAllowed' => $incomeAllowed,
                        'debtAllowed' => $debtAllowed,
                        'courseManagementAllowed' => $courseManagementAllowed,
                        'moveMoneyAllowed' => $moveMoneyAllowed,
                    ]); ?>
                </div>
                <?= $getTabDiv('contract'); ?>
                    <?= $this->render('_view_contract', ['student' => $student]); ?>
                </div>
                <?= $getTabDiv('payment'); ?>
                    <?= $this->render('_view_payment', ['student' => $student]); ?>
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
