<?php

use yii\helpers\Html;
use common\components\helpers\Calendar;
use \backend\models\Event;
use \backend\models\EventMember;
use yii\helpers\Url;

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

$this->registerJs('
Event.init(' . time() . ');
$(\'[data-toggle="tooltip"]\').tooltip();
');

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
                    <button class="btn btn-primary" type="button" onclick="Event.jumpToDate();">Перейти</button>
                </div>
            </div>
        </div>
    </div>

    <div id="messages_place"></div>

    <div class="row">
        <div class="col-xs-12">
            <a class="btn btn-default full-width" href="<?= Url::to(['index', 'date' => $previousDate->format('Y-m-d')]); ?>">
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
                            <div class="full-width btn btn-<?= $statusClass; ?>" onclick="Event.toggleEvent(<?= $event->id; ?>);">
                                <div class="full-width text-left">
                                    <span class="badge"><?= $event->eventTime; ?></span>
                                    <?= $event->group->name; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="event_details hidden" id="event_details_<?= $event->id; ?>" data-status="<?= $event->status; ?>" data-limit-attend-timestamp="<?= $event->limitAttendTimestamp; ?>">
                        <div class="row teacher-block">
                            <div class="col-xs-8 col-sm-9 col-md-8 col-lg-9">
                                <span class="fas fa-user-tie"></span>
                                <?= $event->teacher->name; ?>
                            </div>
                            <div class="col-xs-4 col-sm-3 col-md-4 col-lg-3">
                                <a href="<?= Url::to(['group/view', 'id' => $event->group->id]); ?>" class="btn btn-default btn-sm">
                                    <span class="fas fa-dollar-sign"></span>
                                </a>
                                <a href="<?= Url::to(['group/update', 'id' => $event->group->id]); ?>" class="btn btn-default btn-sm">
                                    <span class="fas fa-pencil-alt"></span>
                                </a>
                            </div>
                        </div>
                        <?php if ($event->status == Event::STATUS_UNKNOWN && $event->eventDateTime <= $limitDate): ?>
                            <div id="messages_place_event_<?= $event->id; ?>"></div>
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
                        <div class="pupils_block <?= $event->status == Event::STATUS_UNKNOWN ? ' hidden' : ''; ?>" data-button-state="0">
                            <div class="row">
                                <div class="col-xs-12">
                                    <table class="table table-condensed">
                                        <thead>
                                            <tr><th colspan="2" class="text-center">Студенты</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($event->members as $member): ?>
                                                <tr><td id="messages_place_event_member_<?= $member->id; ?>" colspan="2"></td></tr>
                                                <tr id="event_member_<?= $member->id; ?>" class="event_member <?php
                                                    if ($member->status == EventMember::STATUS_ATTEND) echo ' success ';
                                                    if ($member->status == EventMember::STATUS_MISS) echo ' danger ';
                                                ?>" data-id="<?= $member->id; ?>" data-status="<?= $member->status; ?>" data-mark="<?= $member->mark; ?>">
                                                    <td>
                                                        <?= $member->groupPupil->user->name; ?>
                                                        <?php
                                                            $noteRows = [];
                                                            if ($member->groupPupil->user->note) {
                                                                $noteRows[] = $member->groupPupil->user->note;
                                                            }
                                                            if ($member->groupPupil->paid_lessons < 0) {
                                                                $noteRows[] = 'долг ' . (0 - $member->groupPupil->paid_lessons)
                                                                    . ' ' . \common\components\helpers\WordForm::getLessonsForm(0 - $member->groupPupil->paid_lessons) . '!';
                                                            }
                                                            if (!empty($noteRows)):
                                                        ?>
                                                            <span class="fas fa-info-circle text-danger" data-toggle="tooltip" data-placement="top" data-html="true" title="<?= implode('<br>', $noteRows); ?>"></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="buttons-column text-right"></td>
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
            <a class="btn btn-default full-width" href="<?= Url::to(['index', 'date' => $nextDate->format('Y-m-d')]); ?>">
                <span class="glyphicon glyphicon-menu-down"></span>
                <?= $nextDate->format('d.m.Y'); ?>
                <small><?= Calendar::$weekDays[$nextDate->format('w')]; ?></small>
            </a>
        </div>
    </div>
</div>
