<?php

use yii\bootstrap4\Html;

/* @var $this yii\web\View */
/* @var $courseStudent \common\models\CourseStudent */
/* @var $moneyLeft int */
/* @var $courseList \common\models\Course[] */

$this->title = 'Перенести деньги студента в другую группу';
$this->params['breadcrumbs'][] = $this->title;

?>

<h1><?= Html::encode($this->title) ?></h1>

<div id="messages_place"></div>
<?= Html::beginForm(); ?>
    <div class="form-group row">
        <label class="col-12 col-sm-3 control-label">Студент</label>
        <div class="col-12 col-sm-9">
            <input readonly class="form-control-plaintext" value="<?= $courseStudent->user->name; ?> (<?= $courseStudent->user->phoneFull; ?>)">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-12 col-sm-3 control-label">Из группы</label>
        <div class="col-12 col-sm-9">
            <input readonly class="form-control-plaintext" value="<?= $courseStudent->course->courseConfig->name; ?>">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-12 col-sm-3 control-label">Сумма</label>
        <div class="col-12 col-sm-9">
            <input readonly class="form-control-plaintext" value="<?= $moneyLeft; ?>">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-12 col-sm-3 control-label">В группу</label>
        <div class="col-12 col-sm-9">
            <select name="money-move[courseId]" class="form-control" required autocomplete="off">
                <?php foreach ($courseList as $course): ?>
                    <option value="<?= $course->id; ?>"><?= $course->courseConfig->name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <button class="form-group btn btn-primary">Перенести</button>
<?= Html::endForm(); ?>
