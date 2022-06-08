<?php

use common\components\DefaultValuesComponent;
use common\models\GroupConfig;
use common\models\GroupPupil;
use yii\helpers\ArrayHelper;
use yii\bootstrap4\ActiveForm;
use common\components\helpers\Html;
use yii\helpers\Url;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $group common\models\Group */
/* @var $groupTypes \common\models\GroupType[] */
/* @var $subjects \common\models\Subject[] */
/* @var $canMoveMoney bool */

$this->title = $group->id ? $group->name : 'Добавить группу';
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$script = 'Main.loadActiveTeachers()';
if (!$group->subject_id) {
    $script .= '.done(function(teacherIds) {
            Group.loadTeacherSelect($("#group-subject_id"));
        })';
}
$script .= ";\n";
if ($group->teacher_id) {
    $script .= 'Group.activeTeacher = ' . $group->teacher_id . ";\n";
}
?>
<div class="group-update">
    <h1>
        <?= Html::encode($this->title) ?>
        <?php if ($group->id): ?>
            <a class="float-right btn btn-outline-dark" href="<?= Url::to(['view', 'id' => $group->id]); ?>">
                Сводка <span class="fas fa-arrow-right"></span>
            </a>
        <?php endif; ?>
    </h1>
    <div class="clearfix"></div>

    <?php $form = ActiveForm::begin(['options' => ['onsubmit' => 'return Group.submitForm();']]); ?>

    <div class="row">
        <div class="col-12 col-lg-6">
            <?= $form->field($group, 'name', ['options' => ['class' => 'form-group']])
                ->textInput(['maxlength' => true, 'required' => true]) ?>

            <?= $form->field($group, 'legal_name', ['options' => ['class' => 'form-group']])
                ->textInput(['maxlength' => true, 'required' => true]) ?>

            <?= $form->field($group, 'type_id', ['options' => ['class' => 'form-group']])
                ->dropDownList(ArrayHelper::map($groupTypes, 'id', 'name'), ['required' => true]); ?>

            <?= $form->field($group, 'subject_id', ['options' => ['class' => 'form-group']])
                ->dropDownList(
                    ArrayHelper::map($subjects, 'id', 'name'),
                    ['onChange' => 'Group.loadTeacherSelect(this);', 'required' => true, 'disabled' => !$group->isNewRecord]
                ); ?>
        </div>
        <div class="col-12 col-lg-6">
            <h3>Параметры</h3>
            <?php
            $teacherList = $group->subject_id ? ArrayHelper::map($group->subject->teachers, 'id', 'name') : [];
            foreach ($group->groupConfigs as $groupConfig) {
                echo $this->render(
                    '_config_card',
                    [
                        'groupConfig' => $groupConfig,
                        'teacherList' => $teacherList,
                    ]
                );
            } ?>
            <?= $this->render(
                    '_config_form',
                    [
                        'groupConfig' => new GroupConfig(),
                        'teacherList' => $teacherList,
                        'form' => $form,
                        'visible' => $group->isNewRecord,
                        'dateFromAllowed' => !$group->isNewRecord,
                    ]);
            ?>
            <?php if (!$group->isNewRecord): ?>
                <button type="button" class="btn btn-info" onclick="Group.addConfig(this);">
                    <span class="fas fa-plus"></span> Добавить
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-group col-12" id="group_date">
        <h5>Группа занимается</h5>
        <?php if (count($group->pupils) == 0): ?>
        <div class="row">
                <?= $form->field(
                        $group,
                        'date_start',
                        [
                            'options' => ['class' => 'form-group col-12 col-md-6 form-inline align-items-start'],
                            'labelOptions' => ['label' => 'C', 'class' => 'mr-2 mt-2'],
                        ]
                    )
                    ->widget(DatePicker::class, ArrayHelper::merge(
                        DefaultValuesComponent::getDatePickerSettings(),
                        ['options' => [
                            'required' => true,
                            'autocomplete' => 'off',
                            'onchange' => 'Main.handleDateRangeFrom(this)',
                            'data' => ['target-to-selector' => '#group-date_end'],
                        ]]
                    )); ?>
                <?= $form->field(
                        $group,
                        'date_end',
                        [
                            'options' => ['class' => 'form-group col-12 col-md-6 form-inline align-items-start'],
                            'labelOptions' => ['label' => 'ДО', 'class' => 'mr-2 mt-2'],
                        ]
                    )
                    ->widget(DatePicker::class, ArrayHelper::merge(
                        DefaultValuesComponent::getDatePickerSettings(),
                        ['options' => [
                            'autocomplete' => 'off',
                            'onchange' => 'Main.handleDateRangeTo(this)',
                            'data' => ['target-from-selector' => '#group-date_start'],
                        ]]
                    )); ?>
        </div>
            <?php
//            $options = ['required' => true];
//            $optionsTo = [];
//            if ($group->date_start) $options['value'] = $group->startDateObject->format('d.m.Y');
//            if ($group->date_end) $optionsTo['value'] = $group->endDateObject->format('d.m.Y');
//            ? >
//            < ? = $form->field($group, 'date_start', ['labelOptions' => ['label' => 'Группа занимается'], 'options' => ['class' => '']])
//            ->widget(DateRangePicker::class, array_merge(
//                DefaultValuesComponent::getDatePickerSettings(),
//                [
//                    'options' => $options,
//                    'optionsTo' => $optionsTo,
//                    'attributeTo' => 'date_end',
//                    'form' => $form, // best for correct client validation
//                    'language' => 'ru',
//                    'labelTo' => 'ДО',
//                    'clientEvents' => [
//                        'changeDate' => 'function(e) {if ($(e.target).attr("name") == "Group[date_end]" && e.format() == $(e.currentTarget).find("input[name=\'Group[date_start]\']").val()) $(e.target).datepicker("clearDates");}',
//                    ]
//                ]
//            ));?>
        <?php else: ?>
            <div class="row align-items-center">
                <div class="col-12 col-sm-6 col-md-auto form-group">
                    C <?= $group->startDateObject->format('d.m.Y'); ?>
                </div>
                <?= Html::hiddenInput('Group[date_start]', $group->startDateObject->format('d.m.Y'), ['id' => 'group-date_start']); ?>
                <?= $form->field(
                        $group,
                        'date_end',
                        [
                            'options' => ['class' => 'form-group col-12 col-sm-6 col-md-auto form-inline align-items-start'],
                            'labelOptions' => ['label' => 'ДО', 'class' => 'mr-2 mt-2'],
                        ]
                    )
                    ->widget(DatePicker::class, ArrayHelper::merge(
                        DefaultValuesComponent::getDatePickerSettings(),
                        [
                            'options' => ['autocomplete' => 'off'],
                            'clientOptions' => ['minDate' => $group->startDateObject->format('d.m.Y')],
                        ]
                    )); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12">
        <h5>Студенты</h5>
        <div id="group_pupils">
            <?php
            $script .= 'Group.isNew = ' . (count($group->pupils) ? 'false' : 'true') . ";\n";
            if ($group->date_start) $script .= 'Group.startDate = "' . $group->startDateObject->format('d.m.Y') . '";' . "\n";
            if ($group->date_end) $script .= 'Group.endDate = "' . $group->endDateObject->format('d.m.Y') . '";' . "\n";
            foreach ($group->activeGroupPupils as $groupPupil): ?>
                <div class="row row-cols-2 justify-content-between mt-3 form-group" id="pupil_row_<?= $groupPupil->id; ?>">
                    <div class="col-9 col-sm-10 col-md-auto">
                        <?= Html::hiddenInput('pupil[]', $groupPupil->user->id); ?>
                        <?= $groupPupil->user->name; ?>
                        <br class="d-md-none">
                        <?php if ($groupPupil->user->phone): ?>
                            (<?= Html::phoneLink($groupPupil->user->phone, $groupPupil->user->phoneFormatted);
                            ?><?= $groupPupil->user->phone2 ? ', ' . Html::phoneLink($groupPupil->user->phone2, $groupPupil->user->phone2Formatted) : ''; ?>)
                        <?php endif; ?>
                    </div>
                    <?php /*
                    <div class="col-3 col-sm-2 col-md-auto">
                        <a href="<?= Url::to(['group/move-pupil', 'groupPupilId' => $groupPupil->id]); ?>" class="btn btn-sm btn-outline-dark">
                            <span class="d-none d-md-inline">Перевести</span>
                            <span class="fas fa-arrow-right"></span>
                        </a>
                    </div> */ ?>
                </div>
                <div class="row group-pupil-block mb-3 border-bottom">
                    <?= $form->field(
                            $groupPupil,
                            'date_start',
                            [
                                'options' => ['class' => 'form-group col-6 col-sm-auto form-inline align-items-start'],
                                'labelOptions' => ['label' => 'C', 'class' => 'mr-2 mt-2'],
                            ]
                        )
                        ->widget(DatePicker::class, ArrayHelper::merge(
                            DefaultValuesComponent::getDatePickerSettings(),
                            [
                                'options' => [
                                    'name' => 'pupil_start[]',
                                    'id' => 'group-pupil-old-date-start-' . $groupPupil->id,
                                    'required' => true,
                                    'autocomplete' => 'off',
                                    'class' => 'form-control pupil-date-start',
                                    'onchange' => 'Main.handleDateRangeFrom(this)',
                                    'data' => ['target-to-closest' => '.group-pupil-block', 'target-to-selector' => '.pupil-date-end'],
                                ],
                                'clientOptions' => [
                                    'minDate' => $group->startDateObject->format('d.m.Y'),
                                    'maxDate' => $group->date_end ? $group->endDateObject->format('d.m.Y') : null,
                                ],
                            ]
                        )); ?>
                    
                    <?= $form->field(
                            $groupPupil,
                            'date_end',
                            [
                                'options' => ['class' => 'form-group col-6 col-sm-auto form-inline align-items-start'],
                                'labelOptions' => ['label' => 'ДО', 'class' => 'mr-2 mt-2'],
                            ]
                        )
                        ->widget(DatePicker::class, ArrayHelper::merge(
                            DefaultValuesComponent::getDatePickerSettings(),
                            [
                                'options' => [
                                    'name' => 'pupil_end[]',
                                    'id' => 'group-pupil-old-date-end-' . $groupPupil->id,
                                    'autocomplete' => 'off',
                                    'class' => 'form-control pupil-date-end',
                                    'onchange' => 'Main.handleDateRangeTo(this); Group.handlePupilEndDate(this);',
                                    'data' => [
                                        'target-from-closest' => '.group-pupil-block',
                                        'target-from-selector' => '.pupil-date-start',
                                        'id' => $groupPupil->id,
                                    ],
                                ],
                                'clientOptions' => [
                                    'minDate' => $groupPupil->startDateObject->format('d.m.Y'),
                                    'maxDate' => $group->date_end ? $group->endDateObject->format('d.m.Y') : null,
                                ],
                            ]
                        )); ?>
                </div>
                <?php
                $script .= 'Group.pupilsActive.push(' . $groupPupil->user->id . ');' . "\n";
            endforeach;
            $this->registerJs($script); ?>
        </div>
        <button class="btn btn-outline-success btn-sm" onclick="return Group.renderPupilForm();"><span class="fas fa-user-plus"></span> Добавить студента</button>
        <hr class="mb-4">

        <?php if (count($group->movedGroupPupils)): ?>
            <h5>Перешли в другие группы</h5>
            <table class="table table-sm">
                <?php foreach ($group->movedGroupPupils as $groupPupil): ?>
                    <tr>
                        <td><?= $groupPupil->user->name; ?></td>
                        <td class="text-right">
                            <?= $groupPupil->startDateObject->format('d.m.Y'); ?> - <?= $groupPupil->endDateObject->format('d.m.Y'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <?php if (count($group->finishedGroupPupils)): ?>
            <h5>Закончили заниматься</h5>
            <table class="table table-sm">
                <?php foreach ($group->finishedGroupPupils as $groupPupil): ?>
                    <tr>
                        <td>
                            <?= $groupPupil->user->name; ?>
                            <?php if ($groupPupil->end_reason): ?>
                                <span class="fas fa-info-circle text-info" data-toggle="tooltip" data-placement="top" data-html="true" title="<?= GroupPupil::END_REASON_LABELS[$groupPupil->end_reason] . '<br>' . nl2br($groupPupil->comment); ?>"></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?= $groupPupil->startDateObject->format('d.m.Y'); ?> - <?= $groupPupil->endDateObject->format('d.m.Y'); ?>
                            <?php if ($canMoveMoney && $groupPupil->moneyLeft > 0): ?>
                                <a href="<?= Url::to(['group/move-money', 'groupPupilId' => $groupPupil->id]); ?>" class="btn btn-sm btn-outline-dark" title="Перенести оставшиеся деньги">
                                    <span class="fas fa-dollar-sign"></span> <span class="fas fa-arrow-right"></span>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="form-group col-12 mt-3">
        <?= Html::submitButton($group->isNewRecord ? 'Добавить' : 'Сохранить', ['class' => 'btn btn-primary']) ?>
        <div id="form-valid" class="my-2 px-2 rounded"></div>
    </div>

    <?php ActiveForm::end(); ?>

    <div class="modal fade" id="end-reason-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="end-reason-form" onsubmit="$('#end-reason-modal').modal('hide'); return false;">
                    <div class="modal-header">
                        <h4 class="modal-title">Причина</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="group_pupil_id" value="0">
                        <?php foreach (GroupPupil::END_REASON_LABELS as $id => $label): ?>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="reason_id" value="<?= $id; ?>" required> <?= $label; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <textarea name="reason_comment" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">OK</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php 
        $this->registerJs(<<<SCRIPT
    $('#end-reason-modal').on('hide.bs.modal', function (e) {
        if (!Group.setEndReason($("#end-reason-form"), false)) {
            e.preventDefault();
        }
    });
SCRIPT
        );
    ?>
</div>
