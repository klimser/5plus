<?php

use common\components\helpers\Calendar;

/* @var $this yii\web\View */
/* @var $group common\models\Group */

$this->title = 'Группа: ' . $group->name;
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $group->name;

?>

<table class="table">
    <tr>
        <td>
            Группа <b><?= $group->name; ?></b> <small class="text-muted">(<?= $group->subject->name; ?>)</small>
        </td>
    </tr>
    <?php if ($group->room_number): ?>
        <tr>
            <td>
                Кабинет <b><?= $group->room_number; ?></b>
            </td>
        </tr>
    <?php endif; ?>
    <tr>
        <td>
            Учитель <b><?= $group->teacher->name; ?></b>
            <?php if ($group->teacher->phone): ?>
                (<small class="text-muted"><?= $group->teacher->phone; ?></small>)
            <?php endif; ?>
            <?php if ($group->teacher->birthday):
                $diffDay = date_diff($group->teacher->birthdayDate, new \DateTime(), false)->days;
                if ($diffDay <= 7 && $diffDay >= 0): ?>
                    <small class="text-muted">день рождения <?= $group->teacher->birthdayDate->format('d.m'); ?></small>
                <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td>
            <div class="row">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="col-xs-2">
                        <b>
                            <span class="visible-xs"><?= Calendar::$weekDaysShort[$i + 1]; ?></span>
                            <span class="hidden-xs"><?= Calendar::$weekDays[$i + 1]; ?></span>
                        </b><br>
                        <small><?= $group->scheduleData ? $group->scheduleData[$i] : ''; ?></small>
                    </div>
                <?php endfor; ?>
            </div>
        </td>
    </tr>
    <tr>
        <td>
            Цена занятия: <b><?= $group->lesson_price; ?></b>
        </td>
    </tr>
    <tr>
        <td>
            Цена со скидкой: <b><?= $group->lesson_price_discount; ?></b>
        </td>
    </tr>
    <tr>
        <td>
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
                                   target="_blank" class="hidden-print">
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
        </td>
    </tr>
</table>
