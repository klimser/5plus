<?php

use common\models\CourseConfig;
use yii\bootstrap4\Html;
use common\components\helpers\Calendar;

/* @var $this yii\web\View */
/* @var $configMap array<int,array<string,array<string,CourseConfig>>> */
/* @var $teacherMap array<int,string> */
/* @var $timeIntervalMap array<string,string[]> */
/* @var $startDate \DateTimeImmutable */
/* @var $endDate \DateTimeImmutable */

$this->title = 'Расписание ' . $startDate->format('d.m.Y') . ' - ' . $endDate->format('d.m.Y');
$this->params['breadcrumbs'][] = $this->title;

$oneDayInterval = new \DateInterval('P1D');

?>
<div class="events-table">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-9">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
    </div>

    <div id="messages_place"></div>

    <table class="table table-hover table-condensed table-bordered small schedule-table">
        <thead>
            <tr>
                <th>Кабинет</th>
                <th>Время</th>
                <?php for ($date = \DateTime::createFromImmutable($startDate); $date < $endDate; $date->add($oneDayInterval)): ?>
                    <th scope="col" class="text-center font-italic">
                        <?= Calendar::$weekDays[$date->format('w')]; ?><br>
                        <span class="small"><?= $date->format('d.m.Y'); ?></span>
                    </th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($configMap as $room => $configs): ?>
                <tr>
                    <td rowspan="<?= count($timeIntervalMap[$room]) + 1; ?>" class="font-weight-bold">
                        <?= $room; ?>
                    </td>
                </tr>
                <?php foreach ($timeIntervalMap[$room] as $interval): ?>
                    <tr>
                        <td class="font-weight-bold font-italic">
                            <nobr><?= $interval; ?></nobr>
                        </td>
                        <?php for ($date = \DateTime::createFromImmutable($startDate); $date < $endDate; $date->add($oneDayInterval)): ?>
                            <?php if (!isset($configs[$date->format('Y-m-d')][$interval])): ?> <td></td>
                            <?php else: ?>
                                <td class="p-1 <?php
                                    if (preg_match('#.*тест.*#iu',$configs[$date->format('Y-m-d')][$interval]->name)) {
                                        echo 'schedule-bg-yellow';
                                    } else {
                                        switch ($configs[$date->format('Y-m-d')][$interval]->course->category_id) {
                                                case 1: echo 'schedule-bg-blue'; break;
                                            case 2: echo 'schedule-bg-orange'; break;
                                            case 3: echo 'schedule-bg-green'; break;
                                        }
                                    }
                                ?>">
                                    <?= $configs[$date->format('Y-m-d')][$interval]->name; ?>
                                    <small><?= $teacherMap[$configs[$date->format('Y-m-d')][$interval]->teacher_id]; ?></small>
                                </td>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
