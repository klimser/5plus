<?php

use backend\models\Event;
use backend\models\EventMember;
use yii\bootstrap4\Html;
use \common\components\helpers\Calendar;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataMap array */
/* @var $eventMap Event[] */
/* @var $date \DateTimeImmutable */
/* @var $daysCount int */
/* @var $course \common\models\Course|null */
/* @var $courses \common\models\Course[] */

$this->title = 'Посещаемость';
$this->params['breadcrumbs'][] = $this->title;
$prevMonth = $date->modify('-1 month');
$nextMonth = $date->modify('+1 month');
?>
<div class="missed-table-index">
    <h1>
        <a href="<?= Url::to(['table', 'year' => $prevMonth->format('Y'), 'month' => $prevMonth->format('n'), 'courseId' => $course ? $course->id : 0]); ?>"
        ><span class="fas fa-arrow-left"></span></a>
        <?= Html::encode($this->title) ?> <?= Calendar::$monthNames[$date->format('n')]; ?> <?= $date->format('Y'); ?>
        <a href="<?= Url::to(['table', 'year' => $nextMonth->format('Y'), 'month' => $nextMonth->format('n'), 'courseId' => $course ? $course->id : 0]); ?>"
        ><span class="fas fa-arrow-right"></span></a>
        <div class="float-right">
            <form id="course-selector">
                <input type="hidden" name="year" value="<?= $date->format('Y'); ?>">
                <input type="hidden" name="month" value="<?= $date->format('n'); ?>">
                <select class="form-control" name="courseId" onchange="document.forms['course-selector'].submit();">
                    <option value="0">Выберите группу...</option>
                    <?php foreach ($courses as $courseEntity): ?>
                        <option value="<?= $courseEntity->id; ?>" <?= $course && $course->id == $courseEntity->id ? ' selected' : ''; ?>>
                            <?= $courseEntity->courseConfig->name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </h1>

    <?php if ($dataMap): ?>
        <table class="table table-bordered table-sm table-responsive-lg text-center">
            <tr>
                <th class="text-left">Студент</th>
                <?php for ($i = 1; $i <= $daysCount; $i++): ?><th class="p-1 <?php
                if (array_key_exists($i, $eventMap)) {
                    switch ($eventMap[$i]->status) {
                        case Event::STATUS_CANCELED: echo ' table-danger '; break;
                        case Event::STATUS_PASSED: echo ' table-success '; break;
                    }
                }
                ?>"><?= $i; ?></th><?php endfor; ?>
            </tr>
        <?php foreach ($dataMap as $studentData): ?>
            <tr>
                <td class="text-left"><?= $studentData[0]; ?></td>
                <?php for ($i = 1; $i <= $daysCount; $i++): ?><td class="p-1 <?php
                 if (array_key_exists($i, $studentData)) {
                     switch ($studentData[$i]['status']) {
                         case EventMember::STATUS_MISS: echo ' table-danger '; break;
                         case EventMember::STATUS_ATTEND: echo ' table-success '; break;
                     }
                 } ?>">
                    <?= array_key_exists($i, $studentData)
                        ? $studentData[$i]['mark'] . ($studentData[$i]['mark_homework'] > 0 ? " / {$studentData[$i]['mark_homework']}" : '')
                        : ''; ?>
                </td><?php endfor; ?>
            </tr>
        <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
