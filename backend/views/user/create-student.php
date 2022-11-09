<?php

use common\components\DefaultValuesComponent;
use common\models\User;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $parent User */
/* @var $parentCompany User */
/* @var $student User */
/* @var $personType int */
/* @var $parentData array */
/* @var $companyData array */
/* @var $consultationData array */
/* @var $welcomeLessonData array */
/* @var $courseData array */
/* @var $studentLimitDate DateTime|null */

$this->title = 'Добавить студента и родителей';
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create">
    <h1><?= Html::encode($this->title) ?></h1>
    <div id="messages_place"></div>
    <div class="user-form">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-12 col-md-6">
                <h2>Студент</h2>
                <?= Html::radioList(
                    'person_type',
                    $personType,
                    [
                        User::ROLE_PARENTS => 'Физ. лицо',
                        User::ROLE_COMPANY => 'Юр. лицо',
                    ],
                    ['itemOptions' => ['onchange' => 'User.changePersonType();', 'class' => 'person_type']]
                ); ?>

                <?= $form->field($student, '[student]name')->textInput(['maxlength' => true, 'required' => true]); ?>

                <?= $form->field($student, '[student]note')->textarea(['maxlength' => true, 'htmlOptions' => ['rows' => 3]]); ?>

                <?= $form->field($student, '[student]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>

                <?= $form->field($student, '[student]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>
            </div>

            <hr class="d-md-none">

            <div id="parents_block" class="parent-edit-block col-12 col-md-6 collapse <?= $personType === User::ROLE_PARENTS ? ' show ' : ''; ?>">
                <h2>Родители</h2>

                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="parent_type" value="none" onclick="Dashboard.changeParentType(this)" <?= $parentData['type'] === 'none' ? ' checked ' : ''; ?>>
                        Студент уже взрослый
                    </label>
                </div>
                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="parent_type" value="exist" onclick="Dashboard.changeParentType(this)" <?= $parentData['type'] === 'exist' ? ' checked ' : ''; ?>>
                        Есть брат (сестра), выбрать родителей из списка
                    </label>
                </div>
                <div class="parent-edit-option parent-edit-exist collapse <?= $parentData['type'] === 'exist' ? ' show ' : ''; ?>">
                    <input type="hidden" class="autocomplete-user-id" name="User[parent][id]">
                    <input class="autocomplete-user form-control" placeholder="начните печатать фамилию или имя" required disabled data-role="<?= User::ROLE_PARENTS; ?>">
                </div>
                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="parent_type" value="new" onclick="Dashboard.changeParentType(this)" <?= $parentData['type'] === 'new' ? ' checked ' : ''; ?>>
                        Добавить родителей
                    </label>
                </div>

                <div class="parent-edit-option parent-edit-new collapse <?= $parentData['type'] === 'new' ? ' show ' : ''; ?>">
                    <?= $form->field($parent, '[parent]name')->textInput(['maxlength' => true, 'disabled' => $personType !== User::ROLE_PARENTS]); ?>

                    <?= $form->field($parent, '[parent]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'required' => true, 'disabled' => $personType !== User::ROLE_PARENTS]); ?>

                    <?= $form->field($parent, '[parent]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'disabled' => $personType !== User::ROLE_PARENTS]); ?>
                </div>
            </div>

            <div id="company_block" class="parent-edit-block col-12 col-md-6 collapse <?= $personType === User::ROLE_COMPANY ? ' show ' : ''; ?>">
                <h2>Компания</h2>

                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="company_type" value="exist" onclick="Dashboard.changeParentType(this, 'company_type')" <?= $companyData['type'] === 'exist' ? ' checked ' : ''; ?>>
                        Выбрать компанию
                    </label>
                </div>
                <div class="parent-edit-option parent-edit-exist collapse <?= $companyData['type'] === 'exist' ? ' show ' : ''; ?>">
                    <input type="hidden" class="autocomplete-user-id" name="User[company][id]">
                    <input class="autocomplete-user form-control" placeholder="начните печатать название" required disabled data-role="<?= User::ROLE_COMPANY; ?>">
                </div>
                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="company_type" value="new" onclick="Dashboard.changeParentType(this, 'company_type')" <?= $companyData['type'] === 'new' ? ' checked ' : ''; ?>>
                        Добавить компанию
                    </label>
                </div>

                <div class="parent-edit-option parent-edit-new collapse <?= $companyData['type'] === 'new' ? ' show ' : ''; ?>">
                    <?= $form->field($parentCompany, '[company]name', ['labelOptions' => ['label' => 'Название']])
                        ->textInput(['maxlength' => true, 'disabled' => $personType !== User::ROLE_COMPANY]); ?>

                    <?= $form->field($parentCompany, '[company]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'required' => true, 'disabled' => $personType !== User::ROLE_COMPANY]); ?>

                    <?= $form->field($parentCompany, '[company]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'disabled' => $personType !== User::ROLE_COMPANY]); ?>
                </div>
            </div>
        </div>

        <?= $this->render('_add_course', [
            'consultationData' => $consultationData,
            'welcomeLessonData' => $welcomeLessonData,
            'courseData' => $courseData,
            'studentLimitDate' => $studentLimitDate,
        ]) ?>

        <hr>
        <div class="form-group text-right">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary']); ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
