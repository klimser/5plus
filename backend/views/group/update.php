<?php

use backend\components\DefaultValuesComponent;
use dosamigos\datepicker\DatePicker;
use dosamigos\datepicker\DateRangePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use common\components\helpers\Calendar;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $group common\models\Group */
/* @var $groupTypes \common\models\GroupType[] */
/* @var $subjects \common\models\Subject[] */
/* @var $canMoveMoney bool */

$this->title = $group->id ? $group->name : 'Добавить группу';
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs(<<<SCRIPT
    Group.loadTeacherMap();
    Group.loadPupilsMap();
SCRIPT
    );
?>
<div class="group-update">
    <h1>
        <?= Html::encode($this->title) ?>
        <?php if ($group->id): ?>
            <a class="pull-right btn btn-default" href="<?= Url::to(['view', 'id' => $group->id]); ?>">
                Сводка <span class="fas fa-arrow-right"></span>
            </a>
        <?php endif; ?>
    </h1>

    <?php $form = ActiveForm::begin(['options' => ['class' => 'row', 'onsubmit' => 'return Group.submitForm();']]); ?>

    <?= $form->field($group, 'name', ['options' => ['class' => 'form-group col-xs-12 col-md-4']])
        ->textInput(['maxlength' => true, 'required' => true]) ?>

    <?= $form->field($group, 'legal_name', ['options' => ['class' => 'form-group col-xs-12 col-md-4']])
        ->textInput(['maxlength' => true, 'required' => true]) ?>

    <?= $form->field($group, 'type_id', ['options' => ['class' => 'form-group col-xs-12 col-md-4']])
        ->dropDownList(ArrayHelper::map($groupTypes, 'id', 'name'), ['required' => true]); ?>

    <?= $form->field($group, 'subject_id', ['options' => ['class' => 'form-group col-xs-12 col-sm-6']])
        ->dropDownList(ArrayHelper::map($subjects, 'id', 'name'), ['onChange' => 'Group.loadTeacherSelect(this);', 'required' => true, 'disabled' => count($group->pupils) > 0]); ?>
    <?php
    if ($group->subject_id) $teachersList = ArrayHelper::map($group->subject->teachers, 'id', 'name');
    else $teachersList = [];
    ?>
    <?= $form->field($group, 'teacher_id', ['options' => ['class' => 'form-group col-xs-12 col-sm-6']])
        ->dropDownList($teachersList, ['required' => true]); ?>

    <?= $form->field($group, 'teacher_rate', ['options' => ['class' => 'form-group col-xs-12 col-sm-6']])
        ->input('number', ['required' => true, 'min' => 0, 'max' => 100, 'step' => '0.01']); ?>

    <?= $form->field($group, 'room_number', ['options' => ['class' => 'form-group col-xs-12 col-sm-6']])
        ->textInput(['maxlength' => true]); ?>

    <div class="form-group col-xs-12" style="display: flex;" id="weekdays">
        <?php for ($i = 0; $i < 7; $i++): ?>
            <div style="flex: auto;" class="one_day_block">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="weekday[<?= $i; ?>]" value="1" <?= empty($group->scheduleData[$i]) ? '' : 'checked'; ?> onchange="Group.toggleWeekday(this);">
                        <span class="visible-xs"><?= Calendar::$weekDaysShort[($i + 1) % 7]; ?></span>
                        <span class="hidden-xs"><?= Calendar::$weekDays[($i + 1) % 7]; ?></span>
                    </label>
                </div>
                <input class="form-control weektime" name="weektime[<?= $i; ?>]" value="<?= $group->scheduleData[$i]; ?>" placeholder="Время" pattern="\d{2}:\d{2}" maxlength="5" required <?= empty($group->scheduleData[$i]) ? 'disabled' : ''; ?>>
            </div>
        <?php endfor; ?>
    </div>

    <?= $form->field($group, 'lesson_price', ['options' => ['class' => 'form-group col-xs-12 col-sm-6']])
        ->input('number', ['placeholder' => 'Только целое число, например: 24000', 'required' => true]); ?>

    <?= $form->field($group, 'lesson_price_discount', ['options' => ['class' => 'form-group col-xs-12 col-sm-6']])
        ->input('number', ['placeholder' => 'Только целое число, например: 18000']); ?>

    <?= $form->field($group, 'lesson_duration', ['options' => ['class' => 'form-group col-xs-12', 'inputTemplate' => '<div class="input-group">{input}<span class="input-group-addon">мин</span></div>']])
        ->input('number', ['placeholder' => 'Только целое число, например: 40', 'required' => true]); ?>

    <div class="form-group col-xs-12" id="group_date">
        <?php if (count($group->pupils) == 0):
            $options = ['required' => true];
            $optionsTo = [];
            if ($group->date_start) $options['value'] = $group->startDateObject->format('d.m.Y');
            if ($group->date_end) $optionsTo['value'] = $group->endDateObject->format('d.m.Y');
            ?>
            <?= $form->field($group, 'date_start', ['labelOptions' => ['label' => 'Группа занимается'], 'options' => ['class' => '']])
            ->widget(DateRangePicker::class, array_merge(
                DefaultValuesComponent::getDatePickerSettings(),
                [
                    'options' => $options,
                    'optionsTo' => $optionsTo,
                    'attributeTo' => 'date_end',
                    'form' => $form, // best for correct client validation
                    'language' => 'ru',
                    'labelTo' => 'ДО',
                    'clientEvents' => [
                        'changeDate' => 'function(e) {if ($(e.target).attr("name") == "Group[date_end]" && e.format() == $(e.currentTarget).find("input[name=\'Group[date_start]\']").val()) $(e.target).datepicker("clearDates");}',
                    ]
                ]
            ));?>
        <?php else: ?>
            <label>Группа занимается</label>
            <div class="row">
                <div class="col-xs-6">
                    <p class="form-control-static pull-left">C <?= $group->startDateObject->format('d.m.Y'); ?></p>
                    <p class="form-control-static pull-right">ДО</p>
                </div>
                <div class="col-xs-6">
                    <input type="hidden" id="group-date_start" value="<?= $group->startDateObject->format('d.m.Y'); ?>">
                    <?= $form->field($group, 'date_end', ['enableLabel' => false])->widget(DatePicker::class, array_merge(
                        DefaultValuesComponent::getDatePickerSettings(),
                        ['options' => ['value' => $group->date_end ? $group->endDateObject->format('d.m.Y') : null]]
                    ));?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-xs-12">
        <label><b>Студенты:</b></label>
        <div id="group_pupils">
            <?php
            $script = 'Group.isNew = ' . (count($group->pupils) ? 'false' : 'true') . ";\n";
            if ($group->date_start) $script .= 'Group.startDate = "' . $group->startDateObject->format('d.m.Y') . '";' . "\n";
            foreach ($group->activeGroupPupils as $groupPupil): ?>
                <div class="row" id="pupil_row_<?= $groupPupil->id; ?>">
                    <?= Html::hiddenInput('pupil[]', $groupPupil->user->id); ?>
                    <div class="col-xs-12">
                        <div class="pull-left">
                            <?= $groupPupil->user->name; ?>
                            <?php if ($groupPupil->user->phone): ?>
                                (<?= $groupPupil->user->phone; ?><?php if($groupPupil->user->phone2): ?>, <?= $groupPupil->user->phone2; ?><?php endif; ?>)
                            <?php endif; ?>
                        </div>
                        <div class="pull-right">
                            <a href="<?= Url::to(['group/move-pupil', 'user' => $groupPupil->user_id, 'group' => $groupPupil->group_id]); ?>" class="btn btn-xs btn-default">Перевести <span class="glyphicon glyphicon-arrow-right"></span></a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="form-group">
                            <?php $settings = DefaultValuesComponent::getDatePickerSettings();
                            $settings['clientOptions']['startDate'] = $group->startDateObject->format('d.m.Y');
                            echo DateRangePicker::widget(array_merge($settings, [
                                'name' => 'pupil_start[]',
                                'value' => $groupPupil->date_start ? $groupPupil->startDateObject->format('d.m.Y') : null,
                                'nameTo' => 'pupil_end[]',
                                'valueTo' => $groupPupil->date_end ? $groupPupil->endDateObject->format('d.m.Y') : null,
                                'options' => ['required' => true, 'class' => 'pupil-start', 'autocomplete' => 'off'],
                                'optionsTo' => ['class' => 'pupil-end', 'autocomplete' => 'off'],
                                'language' => 'ru',
                                'labelTo' => 'ДО',
                                'clientEvents' => [
                                    'changeDate' => 'function(e) {
                                    if ($(e.target).attr("name") == "pupil_end[]") {
                                        if (e.format() == $(e.currentTarget).find("input[name=\'pupil_start[]\']").val()) {
                                            $(e.target).datepicker("clearDates");
                                        }
                                        if ($(e.target).val()) {
                                            $("#end-reason-form").find("input[name=group_pupil_id]").val(' . $groupPupil->id . ');
                                            $("#end-reason-form").find("input[name=reason_id]").prop("checked", false);
                                            let commentText = "";
                                            let pupilRow = $("#pupil_row_' . $groupPupil->id . '");
                                            reasonIdInput = $(pupilRow).find(\'input[name="reason_id[' . $groupPupil->id . ']"]\');
                                            if (reasonIdInput.length > 0) {
                                                $("#end-reason-form").find("input[name=reason_id][value=" + $(reasonIdInput).val() + "]").prop("checked", true);
                                                let reasonCommentInput = $(pupilRow).find(\'input[name="reason_comment[' . $groupPupil->id . ']"]\');
                                                commentText = $(reasonCommentInput).val();
                                            }
                                            $("#end-reason-form").find("input[name=reason_comment]").val(commentText);
                                            $("#end-reason-modal").modal("show");
                                        }
                                    }
                                }',
                                ]
                            ]));?>
                        </div>
                    </div>
                </div>
                <hr class="medium">
                <?php
                $script .= 'Group.pupilsActive.push(' . $groupPupil->user->id . ');' . "\n";
            endforeach;
            $this->registerJs($script); ?>
        </div>
        <button class="btn btn-default btn-xs" onclick="return Group.renderPupilForm();"><span class="icon icon-user-plus"></span> Добавить студента</button>
        <hr>

        <?php if (count($group->movedGroupPupils)): ?>
            <h4>Перешли в другие группы</h4>
            <table class="table table-condensed">
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
            <h4>Закончили заниматься</h4>
            <table class="table table-condensed">
                <?php foreach ($group->finishedGroupPupils as $groupPupil): ?>
                    <tr>
                        <td><?= $groupPupil->user->name; ?></td>
                        <td class="text-right">
                            <?= $groupPupil->startDateObject->format('d.m.Y'); ?> - <?= $groupPupil->endDateObject->format('d.m.Y'); ?>
                            <?php if ($canMoveMoney && $groupPupil->moneyLeft > 0): ?>
                                <a href="<?= Url::to(['group/move-money', 'userId' => $groupPupil->user_id, 'groupId' => $groupPupil->group_id]); ?>" class="btn btn-xs btn-default" title="Перенести оставшиеся деньги">
                                    <span class="fas fa-dollar-sign"></span> <span class="fas fa-arrow-right"></span>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="form-group col-xs-12">
        <?= Html::submitButton($group->isNewRecord ? 'Добавить' : 'Сохранить', ['class' => 'btn btn-primary']) ?>
        <div id="form-valid"></div>
    </div>

    <?php ActiveForm::end(); ?>

    <div class="modal fade" id="end-reason-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="end-reason-form" onsubmit="Group.setEndReason(this); return false;">
                    <div class="modal-header">
                        <h4 class="modal-title">Причина</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="group_pupil_id" value="0">
                        <?php foreach (\common\models\GroupPupil::END_REASON_LABELS as $id => $label): ?>
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
