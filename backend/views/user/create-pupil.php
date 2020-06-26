<?php

use common\components\DefaultValuesComponent;
use common\models\User;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $parent User */
/* @var $parentCompany User */
/* @var $pupil User */
/* @var $personType int */
/* @var $parentData array */
/* @var $companyData array */
/* @var $existedParents User[] */
/* @var $existedCompanies User[] */
/* @var $consultationData array */
/* @var $welcomeLessonData array */
/* @var $groupData array */
/* @var $pupilLimitDate DateTime|null */
/* @var $incomeAllowed bool */
/* @var $contractAllowed bool */

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

                <?= $form->field($pupil, '[pupil]name')->textInput(['maxlength' => true, 'required' => true]); ?>

                <?= $form->field($pupil, '[pupil]note')->textarea(['maxlength' => true, 'htmlOptions' => ['rows' => 3]]); ?>

                <?= $form->field($pupil, '[pupil]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>

                <?= $form->field($pupil, '[pupil]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>
            </div>

            <hr class="d-md-none">

            <div id="parents_block" class="col-12 col-md-6 collapse <?= $personType === User::ROLE_PARENTS ? ' show ' : ''; ?>">
                <h2>Родители</h2>

                <div class="radio">
                    <label>
                        <input type="radio" name="parent_type" value="none" onclick="User.changeParentType()" <?= $parentData['type'] === 'none' ? ' checked ' : ''; ?>> Студент уже взрослый
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="parent_type" value="exist" onclick="User.changeParentType()" <?= $parentData['type'] === 'exist' ? ' checked ' : ''; ?>> Есть брат (сестра), выбрать родителей из списка
                    </label>
                </div>
                <div id="parents_select" class="collapse <?= $parentData['type'] === 'exist' ? ' show ' : ''; ?>">
                    <input type="hidden" class="autocomplete-user-id" value="<?= $parentData['id']; ?>">
                    <input class="autocomplete-user form-control" name="parent_exists" placeholder="начните печатать фамилию или имя" data-role="<?= User::ROLE_PARENTS; ?>" required
                        <?= $parentData['type'] === 'exist' ? '' : ' disabled '; ?>>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="parent_type" value="new" onclick="User.changeParentType()" <?= $parentData['type'] === 'new' ? ' checked ' : ''; ?>> Добавить родителей
                    </label>
                </div>

                <div id="parents_form" class="collapse <?= $parentData['type'] === 'new' ? ' show ' : ''; ?>">
                    <?= $form->field($parent, '[parent]name')->textInput(['maxlength' => true]); ?>

                    <?= $form->field($parent, '[parent]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                    <?= $form->field($parent, '[parent]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                </div>
            </div>

            <div id="company_block" class="col-12 col-md-6 collapse <?= $personType === User::ROLE_COMPANY ? ' show ' : ''; ?>">
                <h2>Компания</h2>

                <div class="radio">
                    <label>
                        <input type="radio" name="company_type" value="exist" onclick="User.changeCompanyType()" <?= $companyData['type'] === 'exist' ? ' checked ' : ''; ?>> Выбрать компанию
                    </label>
                </div>
                <div id="company_select" class="collapse <?= $companyData['type'] === 'exist' ? ' show ' : ''; ?>">
                    <input type="hidden" class="autocomplete-user-id" value="<?= $companyData['id']; ?>">
                    <input class="autocomplete-user form-control" name="parent_exists" placeholder="начните печатать название" data-role="<?= User::ROLE_COMPANY; ?>" required
                        <?= $companyData['type'] === 'exist' ? '' : ' disabled '; ?>>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="company_type" value="new" onclick="User.changeCompanyType()" <?= $companyData['type'] === 'new' ? ' checked ' : ''; ?>> Добавить компанию
                    </label>
                </div>

                <div id="company_form" class="collapse <?= $companyData['type'] === 'new' ? ' show ' : ''; ?>">
                    <?= $form->field($parentCompany, '[parentCompany]name', ['labelOptions' => ['label' => 'Название']])->textInput(['maxlength' => true]); ?>

                    <?= $form->field($parentCompany, '[parentCompany]phoneFormatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                    <?= $form->field($parentCompany, '[parentCompany]phone2Formatted', ['inputTemplate' => DefaultValuesComponent::getPhoneInputTemplate()])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                </div>
            </div>
        </div>

        <?= $this->render('_add_group', [
            'consultationData' => $consultationData,
            'welcomeLessonData' => $welcomeLessonData,
            'groupData' => $groupData,
            'pupilLimitDate' => $pupilLimitDate,
            'incomeAllowed' => $incomeAllowed,
            'contractAllowed' => $contractAllowed,
        ]) ?>

        <hr>
        <div class="form-group text-right">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary']); ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
