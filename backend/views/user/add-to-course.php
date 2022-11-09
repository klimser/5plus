<?php

use common\components\DefaultValuesComponent;
use yii\bootstrap4\ActiveForm;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $student \common\models\User */
/* @var $courses \common\models\Course[] */
/* @var $courseData array */

$this->title = 'Добавить студента в группу';
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$addCourse = array_key_exists('add', $courseData) && $courseData['add'];

?>

<div class="student-add-to-course">
    <h1><?= $student->name; ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <div class="form-group">
        <label for="course">Группа</label>
        <select class="form-control" id="course" name="course[courseId]">
            <?php foreach ($courses as $course): ?>
                <option value="<?= $course->id; ?>" <?= array_key_exists('id', $courseData) && intval($courseData['courseId']) == $course->id ? 'selected' : ''; ?>>
                    <?= $course->courseConfig->name; ?> (с <?= $course->startDateObject->format('d.m.Y') . ($course->endDateObject ? "по {$course->endDateObject->format('d.m.Y')}" : ''); ?>) <?=$course->courseConfig->priceMonth; ?> за месяц
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="course_date_from">Начало занятий</label>
        <?= DatePicker::widget(array_merge(
                DefaultValuesComponent::getDatePickerSettings(),
                [
                    'name' => 'course[date]',
                    'value' => array_key_exists('date', $courseData) ? $courseData['date'] : date('d.m.Y'),
                    'options' => ['id' => 'course_date_from', 'required' => true],
                ]
        ));?>
    </div>

    <button class="btn btn-primary">добавить</button>

    <?php ActiveForm::end(); ?>
</div>
