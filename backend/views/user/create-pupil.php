<?php

use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;


/* @var $this yii\web\View */
/* @var $parent \common\models\User */
/* @var $parentCompany \common\models\User */
/* @var $pupil \common\models\User */
/* @var $groups \common\models\Group[] */
/* @var $existedParents \common\models\User[] */
/* @var $existedCompanies \common\models\User[] */
/* @var $groupData array */
/* @var $paymentData array */
/* @var $contractData array */
/* @var $amount int */
/* @var $companyId int */
/* @var $companies \common\models\Company[] */

$this->title = 'Добавить студента и родителей';
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs(<<<SCRIPT
    Main.initPhoneFormatted();
SCRIPT
);

?>
<div class="user-create">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="user-form">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-xs-12 col-md-6">
                <h2>Студент</h2>
                <?= Html::radioList(
                    'person_type',
                    $parentCompany->name ? \common\models\User::ROLE_COMPANY : \common\models\User::ROLE_PARENTS,
                    [
                        \common\models\User::ROLE_PARENTS => 'Физ. лицо',
                        \common\models\User::ROLE_COMPANY => 'Юр. лицо',
                    ],
                    ['itemOptions' => ['onchange' => 'User.changePersonType();', 'class' => 'person_type']]
                ); ?>

                <?= $form->field($pupil, '[pupil]name')->textInput(['maxlength' => true, 'required' => true]); ?>

                <?= $form->field($pupil, '[pupil]phoneFormatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                    ->textInput(['required' => true, 'maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                <?= $form->field($pupil, '[pupil]phone2Formatted', ['inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
                    ->textInput(['maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}', 'class' => 'form-control phone-formatted']); ?>

                <?= $this->render('_add_group', [
                    'groups' => $groups,
                    'groupData' => $groupData,
                    'paymentData' => $paymentData,
                    'contractData' => $contractData,
                    'companies' => $companies,
                    'amount' => $amount,
                    'companyId' => $companyId,
                ]) ?>
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
                    <select name="parent_exists" class="form-control chosen">
                        <?php foreach ($existedParents as $existedParent): ?>
                            <option value="<?= $existedParent->getId(); ?>" <?= $parent->id == $existedParent->id ? ' selected' : ''; ?>>
                                <?= $existedParent->name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <select name="company_exists" class="form-control chosen">
                        <?php foreach ($existedCompanies as $existedCompany): ?>
                            <option value="<?= $existedCompany->getId(); ?>" <?= $parentCompany->id == $existedCompany->id ? ' selected' : ''; ?>>
                                <?= $existedCompany->name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
            <div class="form-group col-xs-12">
                <?= Html::submitButton('Добавить', ['class' => 'btn btn-primary pull-right']); ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
