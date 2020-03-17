<?php

use common\models\User;
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;

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

$this->title = $pupil->name;
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view">
    <div id="messages_place"></div>
    <div class="user-form">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-xs-12 col-md-6">
                <h2>
                    <?= Html::encode($this->title) ?>
                    <small><small>
                        <?php if ($pupil->individual): ?>
                            <span class="label label-success">Физ. лицо</span>
                        <?php else: ?>
                            <span class="label label-info">Юр. лицо</span>
                        <?php endif; ?>
                    </small></small>
                </h2>
                <?= $form->field($pupil, 'phoneFull')->staticControl(); ?>

                <?= $form->field($pupil, 'phone2Full')->staticControl(); ?>

                <?= $form->field($pupil, 'note')->staticControl(); ?>
            </div>

            <hr class="visible-xs visible-sm">

            <div class="col-xs-12 col-md-6">
                <h2><?= $pupil->individual ? 'Родители' : 'Компания'; ?></h2>

                <?php if (!$pupil->parent_id): ?>
                    <span class="label label-default">Студент уже взрослый</span>
                <?php else: ?>
                    <?= $form->field($parent, 'name')->staticControl(); ?>

                    <?= $form->field($parent, 'phoneFull')->staticControl(); ?>

                    <?= $form->field($parent, 'phone2Full')->staticControl(); ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
<!--                --><?//= $this->render('_add_group', [
//                    'consultationData' => $consultationData,
//                    'welcomeLessonData' => $welcomeLessonData,
//                    'groupData' => $groupData,
//                    'companies' => $companies,
//                    'pupilLimitDate' => $pupilLimitDate,
//                    'incomeAllowed' => $incomeAllowed,
//                    'contractAllowed' => $contractAllowed,
//                ]) ?>
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
