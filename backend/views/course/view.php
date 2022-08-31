<?php

use common\components\helpers\Calendar;
use common\components\helpers\WordForm;
use yii\helpers\Url;
use common\components\helpers\Html;

/* @var $this yii\web\View */
/* @var $group common\models\Course */

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
        Цена занятия (&lt; 12 уроков): <b><?= $group->lesson_price; ?></b><br>
        Цена занятия (&gt; 12 уроков): <b><?= $group->lesson_price_discount; ?></b>
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
        <?php $i = 0; $nowDate = new \DateTime(); foreach ($group->activeGroupPupils as $groupPupil): $i++; ?>
            <div class="row border-bottom py-2">
                <div class="col-8 col-lg-10">
                    <div class="row">
                        <div class="col-1 col-md-auto font-weight-bold pr-0">
                            <?= $i; ?>
                        </div>
                        <div class="col-11 col-md-auto mr-auto">
                            <a href="<?= Url::to(['money/pupil-report', 'userId' => $groupPupil->user_id, 'groupId' => $groupPupil->group_id]); ?>"
                               target="_blank" class="d-print-none d-none d-md-inline">
                                <span class="fas fa-file-invoice-dollar"></span>
                            </a>
                            <?= $groupPupil->user->name; ?>
                            <?php if ($groupPupil->user->parent_id): ?>
                                <span class="fas fa-user-friends point" data-toggle="tooltip" data-placement="top" data-trigger="click hover focus" data-html="true"
                                      title="<?= htmlentities($groupPupil->user->parent->name, ENT_QUOTES); ?><br>
                                      <?= $groupPupil->user->parent->phone . ($groupPupil->user->parent->phone2 ? ', ' . $groupPupil->user->parent->phone2 : ''); ?>"></span>
                            <?php endif; ?>
                            <?php if ($groupPupil->user->note): ?>
                                <br><small><?= nl2br($groupPupil->user->note); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-11 offset-1 col-md-auto offset-md-0 text-md-right">
                            <?= Html::phoneLink($groupPupil->user->phone, $groupPupil->user->phoneFormatted); ?>
                            <?php if($groupPupil->user->phone2): ?>
                                <br>
                                <?= Html::phoneLink($groupPupil->user->phone2, $groupPupil->user->phone2Formatted); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-4 col-lg-2 text-right">
                    <?php if ($groupPupil->paid_lessons < 0): ?>
                        <span class="badge badge-danger">Долг</span> <b><?= round($groupPupil->paid_lessons) * (-1); ?></b> <?= WordForm::getLessonsForm(round($groupPupil->paid_lessons) * (-1)); ?>
                    <?php else: ?>
                        <b><?= round($groupPupil->paid_lessons); ?></b> <?= WordForm::getLessonsForm(round($groupPupil->paid_lessons)); ?>
                    <?php endif; ?><br>
                    <?= number_format($groupPupil->moneyLeft, 0, '.', ' '); ?> сум
                    <?php if ($groupPupil->date_charge_till): ?>
                        <br><span class="badge badge-<?= ($groupPupil->date_charge_till && $groupPupil->chargeDateObject < $nowDate) ? 'danger' : 'success'; ?>">
                            до <?= $groupPupil->chargeDateObject->format('d.m.Y'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
