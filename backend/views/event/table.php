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

    <table class="table table-hover">
        <tbody>
            <?php foreach ($configMap as $teacherId => $configData): ?>
                <tr>
                    <td colspan="8">
                        <h3><?= $teacherMap[$teacherId]; ?></h3>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <?php for ($date = \DateTime::createFromImmutable($startDate); $date < $endDate; $date->add($oneDayInterval)): ?>
                        <th scope="col" class="text-center">
                            <span class="text-muted"><?= Calendar::$weekDaysShort[$date->format('w')]; ?></span><br>
                            <?= $date->format('d.m.Y'); ?>
                        </th>
                    <?php endfor; ?>
                </tr>
                <?php foreach ($configData['intervals'] as $interval => $devNull): ?>
                    <tr>
                        <td>
                            <nobr><?= $interval; ?></nobr>
                        </td>
                        <?php for ($date = \DateTime::createFromImmutable($startDate); $date < $endDate; $date->add($oneDayInterval)): ?>
                            <td>
                                <?php if (isset($configData['configs'][$date->format('Y-m-d')][$interval])): ?>
                                    <?= $configData['configs'][$date->format('Y-m-d')][$interval]->name; ?>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
