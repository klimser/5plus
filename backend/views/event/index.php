<?php

use yii\helpers\Html;
use common\components\helpers\Calendar;
use \backend\models\Event;
use \backend\models\EventMember;

/* @var $this yii\web\View */
/* @var $startDate \DateTime */
/* @var $endDate \DateTime */
/* @var $events \backend\models\Event[] */
/* @var $limitDate \DateTime */

$this->title = 'Расписание ' . $startDate->format('d.m.Y');
$this->params['breadcrumbs'][] = $this->title;

$previousDate = clone $startDate;
$previousDate->modify('-1 day');
$nextDate = clone $startDate;
$nextDate->modify('+1 day');

?>
<div class="events-index">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <h1><?= Html::encode($this->title) ?> <small><?= Calendar::$weekDays[$startDate->format('w')]; ?></small></h1>
        </div>
        <div class="col-xs-12 col-md-6 form-group">
            <div class="row">
                <div class="col-xs-7 col-md-10">
                    <?= \dosamigos\datepicker\DatePicker::widget([
                        'name' => 'date',
                        'value' => date('d.m.Y'),
                        'options' => ['id' => 'jump_to_date'],
                        'clientOptions' => [
                            'autoclose' => true,
                            'format' => 'dd.mm.yyyy',
                            'language' => 'ru',
                            'weekStart' => 1,
                        ]
                    ]);?>
                </div>
                <div class="col-xs-5 col-md-2">
                    <button class="btn btn-primary" type="button" onclick="Event.jumpToDate(this);">Перейти</button>
                </div>
            </div>
        </div>
    </div>

    <div id="messages_place"></div>

    <div class="row">
        <div class="col-xs-12">
            <a class="btn btn-default full-width" href="<?= \yii\helpers\Url::to(['index', 'date' => $previousDate->format('Y-m-d')]); ?>">
                <span class="glyphicon glyphicon-menu-up"></span>
                <?= $previousDate->format('d.m.Y'); ?>
                <small><?= Calendar::$weekDays[$previousDate->format('w')]; ?></small>
            </a>
        </div>
    </div>
    <hr>
    <div class="schedule-table">
        <div class="row">
            <?php $i = 0; foreach ($events as $event):
                $i++;
                $statusClass = 'default';
                switch ($event->status) {
                    case Event::STATUS_PASSED: $statusClass = 'success'; break;
                    case Event::STATUS_CANCELED: $statusClass = 'danger'; break;
                }
                ?>
                <div class="col-xs-12 col-md-4">
                    <div class="row">
                        <div class="col-xs-12">
                            <button type="button" class="full-width text-left btn btn-<?= $statusClass; ?>" onclick="Event.toggleEvent(<?= $event->id; ?>);">
                                <div class="full-width text-left">
                                    <span class="badge"><?= $event->eventTime; ?></span>
                                    <?= $event->group->name; ?>
                                </div>
                            </button>
                        </div>
                    </div>
                    <div class="event_details hidden" id="event_details_<?= $event->id; ?>">
                        <div class="row teacher-block">
                            <div class="col-xs-8 col-sm-9 col-md-8 col-lg-9">
                                <span class="fas fa-user-tie"></span>
                                <?= $event->teacher->name; ?>
                            </div>
                            <div class="col-xs-4 col-sm-3 col-md-4 col-lg-3">
                                <a href="<?= \yii\helpers\Url::to(['group/view', 'id' => $event->group->id]); ?>" class="btn btn-default btn-sm">
                                    <span class="fas fa-dollar-sign"></span>
                                </a>
                                <a href="<?= \yii\helpers\Url::to(['group/update', 'id' => $event->group->id]); ?>" class="btn btn-default btn-sm">
                                    <span class="fas fa-pencil-alt"></span>
                                </a>
                            </div>
                        </div>
                        <?php if ($event->status == Event::STATUS_UNKNOWN && $event->eventDateTime <= $limitDate): ?>
                            <div class="row status_block">
                                <div class="col-xs-6">
                                    <button class="btn btn-success full-width" title="Состоялось" onclick="Event.changeStatus(<?= $event->id; ?>, <?= Event::STATUS_PASSED; ?>);">
                                        <span class="glyphicon glyphicon-ok"></span>
                                    </button>
                                </div>
                                <div class="col-xs-6">
                                    <button class="btn btn-danger full-width" title="Было отменено" onclick="Event.changeStatus(<?= $event->id; ?>, <?= Event::STATUS_CANCELED; ?>);">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="pupils_block <?= $event->status == Event::STATUS_UNKNOWN ? ' hidden' : ''; ?>">
                            <div class="row">
                                <div class="col-xs-12">
                                    <table class="table table-condensed">
                                        <thead>
                                            <tr><th colspan="2" class="text-center">Ученики</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($event->members as $member): ?>
                                                <tr id="event_member_<?= $member->id; ?>" <?php
                                                    if ($member->status == EventMember::STATUS_ATTEND) echo ' class="success"';
                                                    if ($member->status == EventMember::STATUS_MISS) echo ' class="danger"';
                                                ?>>
                                                    <td>
                                                        <?= $member->groupPupil->user->name; ?>
                                                    </td>
                                                    <td class="buttons-column text-right">
                                                        <?php switch ($member->status) {
                                                            case EventMember::STATUS_UNKNOWN: ?>
                                                                <button class="btn btn-success" onclick="Event.setPupilAttendStatus(<?= $member->id; ?>, <?= EventMember::STATUS_ATTEND; ?>);" title="Присутствовал(а)">
                                                                    <span class="glyphicon glyphicon-ok"></span>
                                                                </button>
                                                                <button class="btn btn-danger" onclick="Event.setPupilAttendStatus(<?= $member->id; ?>, <?= EventMember::STATUS_MISS; ?>);" title="Отсутствовал(а)">
                                                                    <span class="glyphicon glyphicon-remove"></span>
                                                                </button>
                                                        <?php break;
                                                            case EventMember::STATUS_ATTEND: ?>
                                                                <?php if ($member->mark > 0): ?>
                                                                    <b><?= $member->mark; ?></b>
                                                                <?php else: ?>
                                                                    <div class="input-group">
                                                                        <input type="number" step="1" min="1" max="5" class="form-control" placeholder="Балл">
                                                                        <span class="input-group-btn">
                                                                            <button class="btn btn-primary" type="button" onclick="Event.setPupilMark(this, <?= $member->id; ?>);">
                                                                                OK
                                                                            </button>
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                        <?php break;
                                                        } ?>

                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="thin">
                </div>
                <?php if ($i % 3 == 0): ?></div><div class="row"><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-xs-12">
            <a class="btn btn-default full-width" href="<?= \yii\helpers\Url::to(['index', 'date' => $nextDate->format('Y-m-d')]); ?>">
                <span class="glyphicon glyphicon-menu-down"></span>
                <?= $nextDate->format('d.m.Y'); ?>
                <small><?= Calendar::$weekDays[$nextDate->format('w')]; ?></small>
            </a>
        </div>
    </div>
</div>
