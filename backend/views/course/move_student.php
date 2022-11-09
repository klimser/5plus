<?php

use \common\models\User;
use yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use \yii\jui\DatePicker;
use \common\components\DefaultValuesComponent;

/* @var $this yii\web\View */
/* @var $courseStudent \common\models\CourseStudent */
/* @var $courseList \common\models\Course[] */

$this->registerJs(<<<SCRIPT
    CourseMove.init();
SCRIPT
);

$this->title = 'Перевести студента в другую группу';
$this->params['breadcrumbs'][] = $this->title;

?>

<h1><?= Html::encode($this->title) ?></h1>

<div id="messages_place"></div>
<form id="move-student-form" onsubmit="CourseMove.moveStudent(); return false;">
    <div class="form-group">
        <label for="student-to-move">Студент</label>
        <?php if ($courseStudent): ?>
            <input type="hidden" id="course-move-id" value="<?= $courseStudent->id; ?>">
            <input readonly class="form-control-plaintext" value="<?= $courseStudent->user->name; ?>">
        <?php else: ?>
            <div>
                <input type="hidden" id="course-move-id">
                <input type="hidden" class="autocomplete-user-id" id="student-id" onchange="CourseMove.loadCourses();">
                <input class="autocomplete-user form-control" id="student-to-move" placeholder="начните печатать фамилию или имя" data-role="<?= User::ROLE_STUDENT; ?>" required>
            </div>
        <?php endif; ?>
    </div>
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label for="course_from">Из группы</label>
                <?php if ($courseStudent): ?>
                    <input type="hidden" id="course_from" value="<?= $courseStudent->course_id; ?>">
                    <input readonly class="form-control-plaintext" value="<?= $courseStudent->course->courseConfig->name; ?>">
                <?php else: ?>
                    <select id="group_from" class="form-control" onchange="CourseMove.selectCourse(this);" required></select>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="move_date">Последний день в старой группе</label>
                <?= DatePicker::widget(ArrayHelper::merge(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'name' => 'date_from',
                        'value' => date('d.m.Y'),
                        'options' => ['id' => 'course-move-date-from', 'required' => true],
                    ]));?>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label for="course_to">В группу</label>
                <select id="course_to" class="form-control" onchange="CourseMove.setCourseToDateInterval(this);" required></select>
            </div>
            <div class="form-group">
                <label for="move_date">Первый день в новой группе</label>
                <?= DatePicker::widget(ArrayHelper::merge(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'name' => 'date_to',
                        'value' => date('d.m.Y'),
                        'options' => ['id' => 'course-move-date-to', 'required' => true],
                    ]));?>
            </div>
        </div>
    </div>

    <button class="btn btn-primary" id="move_student_button">Перевести</button>
</form>
