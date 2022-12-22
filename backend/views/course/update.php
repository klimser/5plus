<?php

use common\components\DefaultValuesComponent;
use common\models\CourseConfig;
use common\models\CourseStudent;
use yii\helpers\ArrayHelper;
use yii\bootstrap4\ActiveForm;
use common\components\helpers\Html;
use yii\helpers\Url;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $course common\models\Course */
/* @var $courseTypes \common\models\CourseType[] */
/* @var $courseCategories \common\models\CourseCategory[] */
/* @var $subjects \common\models\Subject[] */
/* @var $canMoveMoney bool */

$this->title = $course->id ? $course->latestCourseConfig->name : 'Добавить группу';
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$script = 'Main.loadActiveTeachers()';
if (!$course->subject_id) {
    $script .= '.done(function(teacherIds) {
            Course.loadTeacherSelect($("#course-subject_id"));
        })';
}
$script .= ";\n";
if ($course->id) {
    $script .= 'Course.activeTeacher = ' . $course->latestCourseConfig->teacher_id . ";\n";
}
?>
<div class="course-update">
    <h1>
        <?= Html::encode($this->title) ?>
        <?php if ($course->id): ?>
            <a class="float-right btn btn-outline-dark" href="<?= Url::to(['view', 'id' => $course->id]); ?>">
                Сводка <span class="fas fa-arrow-right"></span>
            </a>
        <?php endif; ?>
    </h1>
    <div class="clearfix"></div>

    <?php $form = ActiveForm::begin(['options' => ['onsubmit' => 'return Course.submitForm();']]); ?>

    <div class="row">
        <div class="col-12 col-lg-6">
            <?= $form->field($course, 'type_id', ['options' => ['class' => 'form-group']])
                ->dropDownList(ArrayHelper::map($courseTypes, 'id', 'name'), ['required' => true]); ?>

            <?= $form->field($course, 'category_id', ['options' => ['class' => 'form-group']])
                ->dropDownList(ArrayHelper::map($courseCategories, 'id', 'name'), ['required' => true]); ?>

            <?= $form->field($course, 'subject_id', ['options' => ['class' => 'form-group']])
                ->dropDownList(
                    ArrayHelper::map($subjects, 'id', 'name'),
                    ['onChange' => 'Course.loadTeacherSelect(this);', 'required' => true, 'disabled' => !$course->isNewRecord]
                ); ?>
        </div>
        <div class="col-12 col-lg-6">
            <h3>Параметры</h3>
            <div class="accordion" id="config-list">
                <?php foreach ($course->courseConfigs as $courseConfig): ?>
                    <div class="card">
                        <div class="card-header" id="config-header-<?= $courseConfig->id; ?>">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse"
                                        data-target="#config-<?= $courseConfig->id; ?>" aria-expanded="true"
                                        aria-controls="config-<?= $courseConfig->id; ?>">
                                    <?= $courseConfig->date_from; ?> - <?= $courseConfig->date_to ?? 'сейчас'; ?>
                                </button>
                            </h2>
                        </div>
                        <div id="config-<?= $courseConfig->id; ?>" class="collapse" aria-labelledby="config-header-<?= $courseConfig->id; ?>" data-parent="#config-list">
                            <div class="card-body">
                                <?= $this->render('_config_card', ['courseConfig' => $courseConfig]); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="card">
                    <div class="card-header" id="config-header-new">
                        <h2 class="mb-0">
                            <button class="btn btn-info btn-block text-left" type="button" data-toggle="collapse"
                                    data-target="#config-new" aria-expanded="true" aria-controls="config-new" onclick="Course.addConfig(this);"
                                    <?= $course->isNewRecord ? ' disabled ' : ''; ?>>
                                <span class="fas fa-plus"></span> Добавить
                            </button>
                        </h2>
                    </div>
                    <div id="config-new" class="collapse <?= $course->isNewRecord ? ' show ' : ''; ?>" aria-labelledby="config-header-new" data-parent="#config-list">
                        <div class="card-body">
                            <?php
                                $teacherList = $course->subject_id ? ArrayHelper::map($course->subject->activeTeachers, 'id', 'name') : [];
                            ?>
                            <?= $this->render(
                                '_config_form',
                                [
                                    'courseConfig' => empty($course->courseConfigs) ? new CourseConfig() : clone $course->latestCourseConfig,
                                    'teacherList' => $teacherList,
                                    'form' => $form,
                                    'disabled' => !$course->isNewRecord,
                                ]);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group col-12" id="course_date">
        <h5>Группа занимается</h5>
        <?php if (count($course->students) === 0): ?>
            <div class="row">
                    <?= $form->field(
                            $course,
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
                                'data' => ['target-to-selector' => '#course-date_end'],
                            ]]
                        )); ?>
                    <?= $form->field(
                            $course,
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
                                'data' => ['target-from-selector' => '#course-date_start'],
                            ]]
                        )); ?>
            </div>
        <?php else: ?>
            <div class="row align-items-center">
                <div class="col-12 col-sm-6 col-md-auto form-group">
                    C <?= $course->startDateObject->format('d.m.Y'); ?>
                </div>
                <?= Html::hiddenInput('Course[date_start]', $course->startDateObject->format('d.m.Y'), ['id' => 'course-date_start']); ?>
                <?= $form->field(
                        $course,
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
                            'clientOptions' => ['minDate' => $course->startDateObject->format('d.m.Y')],
                        ]
                    )); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12">
        <h5>Студенты</h5>
        <div id="course_students">
            <?php
            $script .= 'Course.isNew = ' . ($course->id ? 'false' : 'true') . ";\n";
            if ($course->date_start) $script .= 'Course.startDate = "' . $course->startDateObject->format('d.m.Y') . '";' . "\n";
            if ($course->date_end) $script .= 'Course.endDate = "' . $course->endDateObject->format('d.m.Y') . '";' . "\n";
            foreach ($course->activeCourseStudents as $courseStudent): ?>
                <div class="row row-cols-2 justify-content-between mt-3 form-group" id="student_row_<?= $courseStudent->id; ?>">
                    <div class="col-9 col-sm-10 col-md-auto">
                        <?= Html::hiddenInput('student[]', $courseStudent->user->id); ?>
                        <?= $courseStudent->user->name; ?>
                        <br class="d-md-none">
                        <?php if ($courseStudent->user->phone): ?>
                            (<?= Html::phoneLink($courseStudent->user->phone, $courseStudent->user->phoneFormatted);
                            ?><?= $courseStudent->user->phone2 ? ', ' . Html::phoneLink($courseStudent->user->phone2, $courseStudent->user->phone2Formatted) : ''; ?>)
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row course-student-block mb-3 border-bottom">
                    <?= $form->field(
                            $courseStudent,
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
                                    'name' => 'student_start[]',
                                    'id' => 'course-student-old-date-start-' . $courseStudent->id,
                                    'required' => true,
                                    'autocomplete' => 'off',
                                    'class' => 'form-control student-date-start',
                                    'onchange' => 'Main.handleDateRangeFrom(this)',
                                    'data' => ['target-to-closest' => '.course-student-block', 'target-to-selector' => '.student-date-end'],
                                ],
                                'clientOptions' => [
                                    'minDate' => $course->startDateObject->format('d.m.Y'),
                                    'maxDate' => $course->date_end ? $course->endDateObject->format('d.m.Y') : null,
                                ],
                            ]
                        )); ?>
                    
                    <?= $form->field(
                            $courseStudent,
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
                                    'name' => 'student_end[]',
                                    'id' => 'course-student-old-date-end-' . $courseStudent->id,
                                    'autocomplete' => 'off',
                                    'class' => 'form-control student-date-end',
                                    'onchange' => 'Main.handleDateRangeTo(this); Course.handleStudentEndDate(this);',
                                    'data' => [
                                        'target-from-closest' => '.course-student-block',
                                        'target-from-selector' => '.student-date-start',
                                        'id' => $courseStudent->id,
                                    ],
                                ],
                                'clientOptions' => [
                                    'minDate' => $courseStudent->startDateObject->format('d.m.Y'),
                                    'maxDate' => $course->date_end ? $course->endDateObject->format('d.m.Y') : null,
                                ],
                            ]
                        )); ?>
                </div>
                <?php
                $script .= 'Course.studentsActive.push(' . $courseStudent->user->id . ');' . "\n";
            endforeach;
            $this->registerJs($script); ?>
        </div>
        <button class="btn btn-outline-success btn-sm" onclick="return Course.renderStudentForm();"><span class="fas fa-user-plus"></span> Добавить студента</button>
        <hr class="mb-4">

        <?php if (count($course->movedCourseStudents)): ?>
            <h5>Перешли в другие группы</h5>
            <table class="table table-sm">
                <?php foreach ($course->movedCourseStudents as $courseStudent): ?>
                    <tr>
                        <td><?= $courseStudent->user->name; ?></td>
                        <td class="text-right">
                            <?= $courseStudent->startDateObject->format('d.m.Y'); ?> - <?= $courseStudent->endDateObject->format('d.m.Y'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <?php if (count($course->finishedCourseStudents)): ?>
            <h5>Закончили заниматься</h5>
            <table class="table table-sm">
                <?php foreach ($course->finishedCourseStudents as $courseStudent): ?>
                    <tr>
                        <td>
                            <?= $courseStudent->user->name; ?>
                            <?php if ($courseStudent->end_reason): ?>
                                <span class="fas fa-info-circle text-info" data-toggle="tooltip" data-placement="top" data-html="true" title="<?= CourseStudent::END_REASON_LABELS[$courseStudent->end_reason] . '<br>' . nl2br($courseStudent->comment); ?>"></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?= $courseStudent->startDateObject->format('d.m.Y'); ?> - <?= $courseStudent->endDateObject->format('d.m.Y'); ?>
                            <?php if ($canMoveMoney && $courseStudent->moneyLeft > 0): ?>
                                <a href="<?= Url::to(['course/move-money', 'courseStudentId' => $courseStudent->id]); ?>" class="btn btn-sm btn-outline-dark" title="Перенести оставшиеся деньги">
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
        <?= Html::submitButton($course->isNewRecord ? 'Добавить' : 'Сохранить', ['class' => 'btn btn-primary']) ?>
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
                        <input type="hidden" name="course_student_id" value="0">
                        <?php foreach (CourseStudent::END_REASON_LABELS as $id => $label): ?>
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
        if (!Course.setEndReason($("#end-reason-form"), false)) {
            e.preventDefault();
        }
    });
SCRIPT
        );
    ?>
</div>
