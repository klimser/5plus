<?php

use common\components\helpers\WordForm;
use yii\helpers\Html;
use common\components\helpers\Calendar;
use \backend\models\Event;
use \backend\models\EventMember;
use yii\helpers\Url;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $startDate \DateTime */
/* @var $events Event[] */
/* @var $limitDate \DateTime */
/* @var $isTeacher bool */

$this->title = 'Расписание ' . $startDate->format('d.m.Y');
$this->params['breadcrumbs'][] = $this->title;

$previousDate = clone $startDate;
$previousDate->modify('-1 day');
$nextDate = clone $startDate;
$nextDate->modify('+1 day');

$this->registerJs('
Event.init(' . time() . ');
');

?>
<div class="events-index">
    <div class="row">
        <div class="col-12 col-lg-6">
            <h1><?= Html::encode($this->title) ?> <small class="text-muted"><?= Calendar::$weekDays[$startDate->format('w')]; ?></small></h1>
        </div>
        <div class="col-12 col-lg-6 mb-3">
            <form method="get">
                <div class="input-group">
                    <?= DatePicker::widget([
                        'name' => 'date',
                        'value' => $startDate->format('d.m.Y'),
                        'dateFormat' => 'dd.MM.y',
                        'options' => [
                            'id' => 'jump_to_date',
                            'class' => 'form-control',
                            'required' => true,
                            'pattern' => '\d{2}\.\d{2}\.\d{4}',
                            'autocomplete' => 'off',
                        ],
                        'clientOptions' => [
                            'autoclose' => true,
                            'language' => 'ru',
                            'weekStart' => 1,
                        ]
                    ]);?>
                    <div class="input-group-append">
                        <button class="btn btn-primary">Перейти</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="messages_place"></div>

    <div class="row justify-content-end">
        <div class="col-12 col-md-4 col-lg-3">
            <a class="btn btn-outline-dark btn-block" href="<?= Url::to(['index', 'date' => $previousDate->format('Y-m-d')]); ?>">
                <span class="fas fa-chevron-up"></span>
                <?= $previousDate->format('d.m.Y'); ?>
                <small><?= Calendar::$weekDays[$previousDate->format('w')]; ?></small>
            </a>
        </div>
    </div>
    <hr>
    <div class="schedule-table">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3">
            <?php foreach ($events as $event):
                $statusClass = 'outline-dark';
                switch ($event->status) {
                    case Event::STATUS_PASSED: $statusClass = 'success'; break;
                    case Event::STATUS_CANCELED: $statusClass = 'danger'; break;
                }
                ?>
                <div class="col">
                    <div class="w-100 btn btn-<?= $statusClass; ?>" onclick="Event.toggleEvent(<?= $event->id; ?>);">
                        <div class="w-100 text-left">
                            <span class="badge badge-light"><?= $event->eventTime; ?></span>
                            <?= $event->group->name; ?>
                        </div>
                    </div>
                    <div class="event_details collapse card" id="event_details_<?= $event->id; ?>" data-status="<?= $event->status; ?>" data-limit-attend-timestamp="<?= $event->limitAttendTimestamp; ?>">
                        <?php if (!$isTeacher): ?>
                            <div class="card-header p-2">
                                <div class="row teacher-block no-gutters align-items-center">
                                    <div class="col-9">
                                        <span class="fas fa-user-tie"></span>
                                        <?= $event->teacher->name; ?>
                                    </div>
                                    <div class="col-3 text-right">
                                        <div class="btn-group">
                                            <a href="<?= Url::to(['group/view', 'id' => $event->group->id]); ?>" class="btn btn-outline-dark btn-sm">
                                                <span class="fas fa-dollar-sign"></span>
                                            </a>
                                            <a href="<?= Url::to(['group/update', 'id' => $event->group->id]); ?>" class="btn btn-outline-dark btn-sm">
                                                <span class="fas fa-pencil-alt"></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($event->status == Event::STATUS_UNKNOWN && $event->eventDateTime <= $limitDate): ?>
                            <div class="card-body status_block">
                                <div id="messages_place_event_<?= $event->id; ?>"></div>
                                <div class="row">
                                    <div class="col">
                                        <button class="btn btn-success btn-block" title="Состоялось" onclick="Event.changeStatus(<?= $event->id; ?>, <?= Event::STATUS_PASSED; ?>);">
                                            <span class="fas fa-check"></span>
                                        </button>
                                    </div>
                                    <div class="col">
                                        <button class="btn btn-danger btn-block" title="Было отменено" onclick="Event.changeStatus(<?= $event->id; ?>, <?= Event::STATUS_CANCELED; ?>);">
                                            <span class="fas fa-times"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <ul class="list-group list-group-flush pupils_block <?= $event->status == Event::STATUS_UNKNOWN ? ' collapse' : ''; ?>" data-button-state="0">
                            <li class="list-group-item list-group-item-secondary text-center">Студенты</li>
                            <?php foreach ($event->members as $member): ?>
                                <li class="list-group-item p-2">
                                    <div id="messages_place_event_member_<?= $member->id; ?>"></div>
                                    <div id="event_member_<?= $member->id; ?>" class="event_member row no-gutters align-items-center <?php
                                        if ($member->status == EventMember::STATUS_ATTEND) echo ' text-success ';
                                        if ($member->status == EventMember::STATUS_MISS) echo ' text-danger ';
                                    ?>" data-id="<?= $member->id; ?>" data-status="<?= $member->status; ?>" data-mark="<?= $member->mark; ?>">
                                        <div class="col-8">
                                            <?= $member->groupPupil->user->name; ?>
                                            <?php
                                                $noteRows = [];
                                                if ($member->groupPupil->user->note) {
                                                    $noteRows[] = $member->groupPupil->user->note;
                                                }
                                                if ($member->groupPupil->paid_lessons < 0) {
                                                    $noteRows[] = 'долг ' . (0 - $member->groupPupil->paid_lessons)
                                                        . ' ' . WordForm::getLessonsForm(0 - $member->groupPupil->paid_lessons) . '!';
                                                }
                                                if (!empty($noteRows)):
                                            ?>
                                                <span class="fas fa-info-circle text-danger" data-toggle="tooltip" data-placement="top" data-html="true" title="<?= implode('<br>', $noteRows); ?>"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-4 buttons-column text-right"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <hr class="thin">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <hr>
    <div class="row justify-content-end">
        <div class="col-12 col-md-4 col-lg-3">
            <a class="btn btn-outline-dark btn-block" href="<?= Url::to(['index', 'date' => $nextDate->format('Y-m-d')]); ?>">
                <span class="fas fa-chevron-down"></span>
                <?= $nextDate->format('d.m.Y'); ?>
                <small><?= Calendar::$weekDays[$nextDate->format('w')]; ?></small>
            </a>
        </div>
    </div>
</div>
