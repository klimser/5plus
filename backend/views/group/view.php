<?php

use common\components\helpers\Calendar;
use yii\helpers\Url;
use common\components\helpers\Html;

/* @var $this yii\web\View */
/* @var $group common\models\Group */

$this->title = 'Группа: ' . $group->name;
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $group->name;

?>

<div class="row pb-3">
    <div class="col-12 text-center">
        Группа <b><?= $group->name; ?></b> <small class="text-muted">(<?= $group->subject->name; ?>)</small>
    </div>
</div>
<div class="row border-top py-2 text-center">
    <?php for ($i = 0; $i < 7; $i++): ?>
        <div class="col">
            <div class="font-weight-bold">
                <span class="d-md-none"><?= Calendar::$weekDaysShort[($i + 1) % 7]; ?></span>
                <span class="d-none d-md-inline"><?= Calendar::$weekDays[($i + 1) % 7]; ?></span>
            </div>
            <small><?= $group->scheduleData[$i]; ?></small>
        </div>
    <?php endfor; ?>
</div>
<div class="row border-top py-2">
    <div class="col-12 col-md-6">
        Цена занятия: <b><?= $group->lesson_price; ?></b><br>
        Цена со скидкой: <b><?= $group->lesson_price_discount; ?></b>
    </div>
    <div class="col-12 col-md-6">
        Учитель <b><?= $group->teacher->name; ?></b>
        <?php if ($group->teacher->phone): ?>
            (<small class="text-muted"><?= Html::phoneLink($group->teacher->phone); ?></small>)
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
<div class="row border-top pt-3">
    <div class="col-12">
        <h4>Студенты:</h4>
        <table class="table table-sm table-striped">
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
                        <a href="<?= Url::to(['money/pupil-report', 'userId' => $groupPupil->user_id, 'groupId' => $groupPupil->group_id]); ?>"
                           target="_blank" class="hidden-print hidden-xs hidden-sm">
                            <span class="fas fa-file-invoice-dollar"></span>
                        </a>
                        <?= $groupPupil->user->name; ?>
                        <?php if ($groupPupil->user->parent_id): ?>
                            <span class="fas fa-user-friends point" data-toggle="tooltip" data-placement="top" data-trigger="click hover focus" data-html="true"
                                  title="<?= $groupPupil->user->parent->name; ?><br>
                                  <?= $groupPupil->user->parent->phone . ($groupPupil->user->parent->phone2 ? ', ' . $groupPupil->user->parent->phone2 : ''); ?>"></span>
                        <?php endif; ?>
                        <?php if ($groupPupil->user->note): ?>
                            <br><small><?= nl2br($groupPupil->user->note); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <?= Html::phoneLink($groupPupil->user->phone, $groupPupil->user->phoneFormatted); ?>
                        <?php if($groupPupil->user->phone2): ?>
                            <br>
                            <?= Html::phoneLink($groupPupil->user->phone2, $groupPupil->user->phone2Formatted); ?>
                        <?php endif; ?>
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
