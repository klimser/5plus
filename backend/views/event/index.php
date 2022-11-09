<?php

use yii\bootstrap4\Html;
use common\components\helpers\Calendar;
use \backend\models\Event;
use yii\helpers\Url;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $startDate \DateTimeImmutable */
/* @var $events Event[] */
/* @var $limitDate \DateTime */
/* @var $isTeacher bool */
/* @var $isAdmin bool */

$this->title = 'Расписание ' . $startDate->format('d.m.Y');
$this->params['breadcrumbs'][] = $this->title;

$previousDate = $startDate->modify('-1 day');
$nextDate = $startDate->modify('+1 day');

$this->registerJs('
StudyEvent.init(' . time() . ', ' . $limitDate->getTimestamp() . ', ' . ($isTeacher ? 'true' : 'false') . ', ' . ($isAdmin ? 'true' : 'false') . ');
');

?>
<div class="events-index">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-9">
            <h1><?= Html::encode($this->title) ?> <small class="text-muted"><?= Calendar::$weekDays[$startDate->format('w')]; ?></small></h1>
        </div>
        <div class="col-12 col-md-4 col-lg-3 mb-3">
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

    <div class="row">
        <div class="col">
            <a class="btn btn-outline-dark btn-block text-left" href="<?= Url::to(['index', 'date' => $previousDate->format('d.m.Y')]); ?>">
                <span class="fas fa-chevron-left"></span>
                <?= $previousDate->format('d.m.Y'); ?>
                <small><?= Calendar::$weekDays[$previousDate->format('w')]; ?></small>
            </a>
        </div>
        <div class="col">
            <a class="btn btn-outline-dark btn-block text-right" href="<?= Url::to(['index', 'date' => $nextDate->format('d.m.Y')]); ?>">
                <?= $nextDate->format('d.m.Y'); ?>
                <small><?= Calendar::$weekDays[$nextDate->format('w')]; ?></small>
                <span class="fas fa-chevron-right"></span>
            </a>
        </div>
    </div>
    <hr>
    <div class="schedule-table">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3">
            <?php foreach ($events as $event):
                $statusClass = match ($event->status) {
                    Event::STATUS_PASSED => 'success',
                    Event::STATUS_CANCELED => 'danger',
                    default => 'outline-dark',
                };
                ?>
                <div class="col mb-2">
                    <div id="event-button-<?= $event->id; ?>" class="w-100 btn btn-<?= $statusClass; ?>" onclick="StudyEvent.openEvent(<?= $event->id; ?>);">
                        <div class="w-100 text-left">
                            <span class="badge badge-light"><?= $event->eventTime; ?></span>
                            <?= $event->courseConfig->name; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="modal-event" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body card p-0">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-auto"><span class="badge badge-warning" id="event-time"></span></div>
                            <div class="col" id="event-teacher"></div>
                        </div>
                    </div>
                    <div id="event-messages-place" class="card-body p-0"></div>
                    <div id="event-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>
