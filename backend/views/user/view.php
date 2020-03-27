<?php

use common\models\User;
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;

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
    
    <?php
        $getTabLi = function(string $tabId, string $title) use ($pupil, $activeTab) {
            $elemId = $tabId . '-tab-' . $pupil->id;
            return '<li role="presentation" ' . ($activeTab === $tabId ? ' class="active" ' : '') . '>'
                . '<a href="#' . $elemId . '" aria-controls="' . $elemId . '" role="tab" data-toggle="tab">' . $title . '</a></li>';
        };
        
        $getTabDiv = function(string $tabId) use ($pupil, $activeTab) {
            return '<div role="tabpanel" class="tab-pane ' . ($activeTab === $tabId ? ' active ' : '') . '" id="' . $tabId . '-tab-' . $pupil->id . '">';
        };
    ?>

    <div class="row">
        <div class="col-xs-12">
            <ul class="nav nav-tabs" role="tablist">
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
        <div class="form-group col-xs-12 text-right">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary']); ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
