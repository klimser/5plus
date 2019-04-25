<?php

use yii\helpers\Html;
use \common\components\helpers\Calendar;

/* @var $this yii\web\View */
/* @var $dataMap array */
/* @var $date \DateTime */
/* @var $daysCount int */
/* @var $group \common\models\Group|null */
/* @var $groups \common\models\Group[] */

$this->title = 'Посещаемость';
$this->params['breadcrumbs'][] = $this->title;
$prevMonth = clone $date;
$prevMonth->modify('-1 month');
$nextMonth = clone $date;
$nextMonth->modify('+1 month');
?>
<div class="missed-table-index">
    <h1>
        <a href="<?= \yii\helpers\Url::to(['table', 'year' => $prevMonth->format('Y'), 'month' => $prevMonth->format('n'), 'groupId' => $group ? $group->id : 0]); ?>"
        ><span class="fas fa-arrow-left"></span></a>
        <?= Html::encode($this->title) ?> <?= Calendar::$monthNames[$date->format('n')]; ?> <?= $date->format('Y'); ?>
        <a href="<?= \yii\helpers\Url::to(['table', 'year' => $nextMonth->format('Y'), 'month' => $nextMonth->format('n'), 'groupId' => $group ? $group->id : 0]); ?>"
        ><span class="fas fa-arrow-right"></span></a>
        <div class="pull-right">
            <form id="group-selector">
                <input type="hidden" name="year" value="<?= $date->format('Y'); ?>">
                <input type="hidden" name="month" value="<?= $date->format('n'); ?>">
                <select class="form-control" name="groupId" onchange="document.forms['group-selector'].submit();">
                    <option value="0">Выберите группу...</option>
                    <?php foreach ($groups as $groupEntity): ?>
                        <option value="<?= $groupEntity->id; ?>" <?= $group && $group->id == $groupEntity->id ? ' selected' : ''; ?>>
                            <?= $groupEntity->name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </h1>

    <?php if ($dataMap): ?>
        <table class="table table-bordered table-condensed">
            <tr>
                <th>Студент</th>
                <?php for ($i = 1; $i <= $daysCount; $i++): ?><th><?= $i; ?></th><?php endfor; ?>
            </tr>
        <?php foreach ($dataMap as $pupilData): ?>
            <tr>
                <td><?= $pupilData[0]; ?></td>
                <?php for ($i = 1; $i <= $daysCount; $i++): ?><td<?php
                 if (array_key_exists($i, $pupilData)) {
                     switch ($pupilData[$i]) {
                         case \backend\models\EventMember::STATUS_MISS: echo ' class="danger"'; break;
                         case \backend\models\EventMember::STATUS_ATTEND: echo ' class="success"'; break;
                     }
                 } ?>></td><?php endfor; ?>
            </tr>
        <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>