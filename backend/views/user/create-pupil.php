<?php

use common\models\User;
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;


/* @var $this yii\web\View */
/* @var $requestData array */
/* @var $parent User */
/* @var $parentCompany User */
/* @var $pupil User */
/* @var $groups \common\models\Group[] */
/* @var $subjects \common\models\Subject[] */
/* @var $existedParents User[] */
/* @var $existedCompanies User[] */
/* @var $groupData array */
/* @var $paymentData array */
/* @var $contractData array */
/* @var $consultationData array */
/* @var $welcomeLessonData array */
/* @var $amount int */
/* @var $companyId int */
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
                    $parentCompany->name ? User::ROLE_COMPANY : User::ROLE_PARENTS,
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

            <div id="parents_block" class="col-xs-12 col-md-6 <?= $parentCompany->name ? ' hidden' : ''; ?>">
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
                <div id="parents_select" <?= !$parent->id ? ' class="hidden"' : ''; ?>>
                    <?= Html::dropDownList('parent_exists', $parent->id, ArrayHelper::map($existedParents, 'id', 'name'), ['class' => 'form-control chosen']); ?>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="parent_type" value="new" checked onclick="User.changeParentType()"> Добавить родителей
                    </label>
                </div>

                <div id="parents_form" <?= $parent->id ? ' class="hidden"' : ''; ?>>
                    <?= $form->field($parent, '[parent]name')->textInput(['maxlength' => true]); ?>

                    <?= $form->field($parent, '[parent]phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                    <?= $form->field($parent, '[parent]phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                        ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>
                </div>
            </div>

            <div id="company_block" class="col-xs-12 col-md-6 <?= $parentCompany->name ? '' : ' hidden'; ?>">
                <h2>Компания</h2>

                <div class="radio">
                    <label>
                        <input type="radio" name="company_type" value="exist" onclick="User.changeCompanyType()"> Выбрать компанию из списка
                    </label>
                </div>
                <div id="company_select" <?= !$parentCompany->id ? ' class="hidden"' : ''; ?>>
                    <?= Html::dropDownList('parent_exists', $parentCompany->id, ArrayHelper::map($existedCompanies, 'id', 'name'), ['class' => 'form-control chosen']); ?>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="company_type" value="new" checked onclick="User.changeCompanyType()"> Добавить компанию
                    </label>
                </div>

                <div id="company_form" <?= $parentCompany->id ? ' class="hidden"' : ''; ?>>
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
                    'requestData' => $requestData,
                    'groups' => $groups,
                    'subjects' => $subjects,
                    'groupData' => $groupData,
                    'paymentData' => $paymentData,
                    'contractData' => $contractData,
                    'consultationData' => $consultationData,
                    'welcomeLessonData' => $welcomeLessonData,
                    'companies' => $companies,
                    'amount' => $amount,
                    'companyId' => $companyId,
                    'incomeAllowed' => $incomeAllowed,
                    'contractAllowed' => $contractAllowed,
                ]) ?>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-xs-12">
                <?= Html::submitButton('Добавить', ['class' => 'btn btn-primary pull-right']); ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
