<?php

use common\models\User;
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $pupil User */
/* @var $incomeAllowed bool */
/* @var $contractAllowed bool */
/* @var $groupManagementAllowed bool */
/* @var $moveMoneyAllowed bool */
/* @var $welcomeLessonsAllowed bool */

$this->title = $pupil->name;
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view">
    <div id="user-view-messages-place"></div>
    <?php $form = ActiveForm::begin(['options' => ['onsubmit' => 'Dashboard.savePupil(this); return false;']]); ?>
    <?= $form->field($pupil, 'id', ['template' => '{input}', 'options' => ['class' => []]])->hiddenInput(); ?>

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

            <?php if ($pupil->phone2): ?>
                <?= $form->field($pupil, 'phone2Full')->staticControl(); ?>
            <?php endif; ?>

            <?php if ($pupil->note): ?>
                <?= $form->field($pupil, 'note')->staticControl(); ?>
            <?php endif; ?>
        </div>

        <hr class="visible-xs visible-sm">

        <div class="col-xs-12 col-md-6">
            <h2><?= $pupil->individual ? 'Родители' : 'Компания'; ?></h2>

            <?php if (!$pupil->parent_id): ?>
                <span class="label label-default">Студент уже взрослый</span>
            <?php else: ?>
                <?= $form->field($pupil->parent, 'name')->staticControl(); ?>

                <?= $form->field($pupil->parent, 'phoneFull')->staticControl(); ?>

                <?= $form->field($pupil->parent, 'phone2Full')->staticControl(); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#consultation-tab" aria-controls="consultation-tab" role="tab" data-toggle="tab">консультации</a></li>
                <?php if ($welcomeLessonsAllowed): ?>
                    <li role="presentation"><a href="#welcome_lesson-tab" aria-controls="welcome_lesson-tab" role="tab" data-toggle="tab">пробные уроки</a></li>
                <?php endif; ?>
                <li role="presentation"><a href="#group-tab" aria-controls="group-tab" role="tab" data-toggle="tab">группы</a></li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="consultation-tab">
                    <?= $this->render('_view_consultation', ['pupil' => $pupil]); ?>
                </div>
                <?php if ($welcomeLessonsAllowed): ?>
                    <div role="tabpanel" class="tab-pane" id="welcome_lesson-tab">
                        <?= $this->render('_view_welcome_lesson', ['pupil' => $pupil]); ?>
                    </div>
                <?php endif; ?>
                <div role="tabpanel" class="tab-pane" id="group-tab">
                    <?= $this->render('_view_group', [
                        'pupil' => $pupil,
                        'incomeAllowed' => $incomeAllowed,
                        'groupManagementAllowed' => $groupManagementAllowed,
                        'moveMoneyAllowed' => $moveMoneyAllowed,
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="form-group col-xs-12 text-right">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary']); ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
