<?php

use common\components\helpers\Calendar;

/* @var $this yii\web\View */
/* @var $group common\models\Group */

$this->title = 'Группа: ' . $group->name;
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $group->name;

?>

<div class="row">
    <div class="col-xs-12 text-center">
        Группа <b><?= $group->name; ?></b> <small class="text-muted">(<?= $group->subject->name; ?>)</small>
    </div>
</div>
<hr class="medium">
<table style="width: 100%;">
    <tr style="vertical-align: top;">
        <?php for ($i = 0; $i < 7; $i++): ?>
            <td>
                <div>
                    <b>
                        <span class="visible-xs"><?= Calendar::$weekDaysShort[($i + 1) % 7]; ?></span>
                        <span class="hidden-xs"><?= Calendar::$weekDays[($i + 1) % 7]; ?></span>
                    </b>
                </div>
                <div><small><?= $group->scheduleData[$i]; ?></small></div>
            </td>
        <?php endfor; ?>
    </tr>
</table>
<hr class="thin">
<div class="row">
    <div class="col-xs-12 col-sm-6">
        Цена занятия: <b><?= $group->lesson_price; ?></b><br>
        Цена со скидкой: <b><?= $group->lesson_price_discount; ?></b>
    </div>
    <div class="col-xs-12 col-sm-6">
        Учитель <b><?= $group->teacher->name; ?></b>
        <?php if ($group->teacher->phone): ?>
            (<small class="text-muted"><?= $group->teacher->phone; ?></small>)
        <?php endif; ?>
        <?php if ($group->teacher->birthday):
            $diffDay = date_diff($group->teacher->birthdayDate, new \DateTime(), false)->days;
            if ($diffDay <= 7 && $diffDay >= 0): ?>
                <br><small class="text-muted">день рождения <?= $group->teacher->birthdayDate->format('d.m'); ?></small>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($group->room_number): ?>
            <br>Кабинет <b><?= $group->room_number; ?></b>
        <?php endif; ?>
    </div>
</div>
<div class="row">

</div>
<div class="row">
    <div class="col-xs-12">
        <h4>Ученики:</h4>
        <table class="table table-condensed table-striped">
            <thead>
            <tr>
                <th></th>
                <th>Имя</th>
                <th>Телефон</th>
                <th>Оплата до</th>
                <th>Занятия</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 0; $nowDate = new \DateTime(); foreach ($group->activeGroupPupils as $groupPupil): $i++; ?>
                <tr>
                    <td><?= $i; ?></td>
                    <td>
                        <a href="<?= \yii\helpers\Url::to(['money/pupil-report', 'userId' => $groupPupil->user_id, 'groupId' => $groupPupil->group_id]); ?>"
                           target="_blank" class="hidden-print hidden-xs hidden-sm">
                            <span class="fas fa-file-invoice-dollar"></span>
                        </a>
                        <?= $groupPupil->user->name; ?>
                    </td>
                    <td>
                        <?= $groupPupil->user->phone; ?>
                        <?php if($groupPupil->user->phone2): ?><br> <?= $groupPupil->user->phone2; ?><?php endif; ?>
                    </td>
                    <td <?php if ($groupPupil->date_charge_till && $groupPupil->chargeDateObject < $nowDate): ?>class="danger"<?php endif; ?>>
                        <?= $groupPupil->date_charge_till ? $groupPupil->chargeDateObject->format('d.m.Y') : ''; ?>
                    </td>
                    <?php if ($groupPupil->paid_lessons < 0): ?>
                        <td class="danger">Долг <?= round($groupPupil->paid_lessons) * (-1); ?> занятий</td>
                    <?php else: ?>
                        <td><?= round($groupPupil->paid_lessons); ?> занятий</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>