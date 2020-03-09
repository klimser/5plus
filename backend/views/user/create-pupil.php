<?php

use common\models\User;
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;
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
/* @var $companies \common\models\Company[] */
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
            <div class="col-xs-12 col-md-6">
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

                <?= $form->field($pupil, '[pupil]phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>

                <?= $form->field($pupil, '[pupil]phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted', 'onchange' => 'User.checkPhone(this);']); ?>
            </div>

            <hr class="visible-xs visible-sm">

            <div id="parents_block" class="col-xs-12 col-md-6 <?= $personType === User::ROLE_PARENTS ? '' : ' hidden '; ?>">
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
                <div id="parents_select" <?= $parentData['type'] === 'exist' ? '' : ' class="hidden" '; ?>>
                    <?= Html::dropDownList('parent_exists', $parentData['id'], ArrayHelper::map($existedParents, 'id', 'name'), ['class' => 'form-control chosen']); ?>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="parent_type" value="new" onclick="User.changeParentType()" <?= $parentData['type'] === 'new' ? ' checked ' : ''; ?>> Добавить родителей
                    </label>
                </div>

                <div id="parents_form" <?= $parentData['type'] === 'new' ? '' : ' class="hidden" '; ?>>
                    <?= $form->field($parent, '[parent]name')->textInput(['maxlength' => true]); ?>

                    <?= $form->field($parent, '[parent]phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                    <?= $form->field($parent, '[parent]phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                </div>
            </div>

            <div id="company_block" class="col-xs-12 col-md-6 <?= $personType === User::ROLE_COMPANY ? '' : ' hidden '; ?>">
                <h2>Компания</h2>

                <div class="radio">
                    <label>
                        <input type="radio" name="company_type" value="exist" onclick="User.changeCompanyType()" <?= $companyData['type'] === 'exist' ? ' checked ' : ''; ?>> Выбрать компанию из списка
                    </label>
                </div>
                <div id="company_select" <?= $companyData['type'] === 'exist' ? '' : ' class="hidden" '; ?>>
                    <?= Html::dropDownList('parent_exists', $companyData['id'], ArrayHelper::map($existedCompanies, 'id', 'name'), ['class' => 'form-control chosen']); ?>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="company_type" value="new" onclick="User.changeCompanyType()" <?= $companyData['type'] === 'new' ? ' checked ' : ''; ?>> Добавить компанию
                    </label>
                </div>

                <div id="company_form" <?= $companyData['type'] === 'new' ? '' : ' class="hidden" '; ?>>
                    <?= $form->field($parentCompany, '[parentCompany]name', ['labelOptions' => ['label' => 'Название']])->textInput(['maxlength' => true]); ?>

                    <?= $form->field($parentCompany, '[parentCompany]phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                    <?= $form->field($parentCompany, '[parentCompany]phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <?= $this->render('_add_group', [
                    'consultationData' => $consultationData,
                    'welcomeLessonData' => $welcomeLessonData,
                    'groupData' => $groupData,
                    'companies' => $companies,
                    'pupilLimitDate' => $pupilLimitDate,
                    'incomeAllowed' => $incomeAllowed,
                    'contractAllowed' => $contractAllowed,
                ]) ?>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="form-group col-xs-12">
                <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary pull-right']); ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
