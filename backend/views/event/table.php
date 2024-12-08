<?php

use common\models\CourseConfig;
use yii\bootstrap4\Html;
use common\components\helpers\Calendar;

/* @var $this yii\web\View */
/* @var $configMap array<int,array{intervals:string[],configs:array<string,array<string,CourseConfig>>}> */
/* @var $teacherMap array<int,\common\models\Teacher> */
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

    <table class="table table-hover table-condensed table-bordered small">
        <thead>
            <tr>
                <th></th>
                <th></th>
                <?php for ($date = \DateTime::createFromImmutable($startDate); $date < $endDate; $date->add($oneDayInterval)): ?>
                    <th scope="col" class="text-center font-italic">
                        <?= Calendar::$weekDays[$date->format('w')]; ?><br>
                        <span class="small"><?= $date->format('d.m.Y'); ?></span>
                    </th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($configMap as $teacherId => $configData): ?>
                <tr>
                    <td rowspan="<?= count($configData['intervals']) + 1; ?>" class="font-weight-bold">
                        <?= implode(', ', $configData['rooms']); ?> <?= $teacherMap[$teacherId]; ?>
                    </td>
                </tr>
                <?php foreach ($configData['intervals'] as $interval): ?>
                    <tr>
                        <td class="font-weight-bold font-italic">
                            <nobr><?= $interval; ?></nobr>
                        </td>
                        <?php for ($date = \DateTime::createFromImmutable($startDate); $date < $endDate; $date->add($oneDayInterval)): ?>
                            <?php if (!isset($configData['configs'][$date->format('Y-m-d')][$interval])): ?> <td></td>
                            <?php else: ?>
                                <td
                                    <?php
                                    if (preg_match('#.*тест.*#iu',$configData['configs'][$date->format('Y-m-d')][$interval]->name)) {
                                        echo 'class="schedule-bg-yellow"';
                                    } else {
                                        switch ($configData['configs'][$date->format('Y-m-d')][$interval]->course->category_id) {
                                                case 1: echo 'class="schedule-bg-blue"'; break;
                                            case 2: echo 'class="schedule-bg-orange"'; break;
                                            case 3: echo 'class="schedule-bg-green"'; break;
                                        }
                                    }
                                ?>
                                >
                                        <?= $configData['configs'][$date->format('Y-m-d')][$interval]->name; ?>

                                </td>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
