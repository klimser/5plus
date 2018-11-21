<?php

use yii\helpers\Html;
use common\components\helpers\Calendar;

/* @var $this yii\web\View */
/* @var $eventMonth \DateTime */
/* @var $user \common\models\User */
/* @var $eventMap \backend\models\EventMember[][] */
/* @var $groupMap array */

$this->title = 'Дневник';
if (Yii::$app->user->identity->role == \common\models\User::ROLE_ROOT) {
    $this->params['breadcrumbs'][] = ['label' => 'Студенты', 'url' => ['schedule']];
    $this->params['breadcrumbs'][] = $user->name;
} elseif (Yii::$app->user->identity->role == \common\models\User::ROLE_PARENTS && count(Yii::$app->user->identity->children) > 1) {
    $this->params['breadcrumbs'][] = ['label' => 'Мои дети', 'url' => ['schedule']];
    $this->params['breadcrumbs'][] = $this->title . ': ' . $user->name;
} else {
    $this->params['breadcrumbs'][] = $this->title;
}

$queryParams = Yii::$app->request->getQueryParams();
$intervalDay = new \DateInterval('P1D');
$intervalMonth = new \DateInterval('P1M');

?>
<div class="events-schedule">
    <div class="row">
        <div class="col-xs-12">
            <h1 class="pull-left no-margin-top"><?= Html::encode($this->title) ?></h1>
            <?= \backend\components\DebtWidget::widget(['user' => $user]); ?>
        </div>
        <div class="clearfix"></div>
    </div>
    <?php $eventMonth->sub($intervalMonth); ?>
    <div class="row">
        <div class="col-xs-12">
            <a class="btn btn-default full-width" href="<?= \yii\helpers\Url::to(array_merge($queryParams, ['schedule', 'month' => $eventMonth->format('Y-m')])); ?>">
                <span class="glyphicon glyphicon-menu-up"></span> <?= Calendar::$monthNames[$eventMonth->format('n')]; ?> <?= $eventMonth->format('Y'); ?>
            </a>
        </div>
    </div>
    <?php $eventMonth->add($intervalMonth); ?>
    <div class="row">
        <div class="col-xs-12">
            <h3><?= Calendar::$monthNames[$eventMonth->format('n')]; ?> <?= $eventMonth->format('Y'); ?></h3>
        </div>
    </div>

    <?php if (!empty($groupMap)): ?>
        <div class="row">
            <div class="col-xs-12">
                <hr><h4>Занятия в группах:</h4>
                <?php foreach ($groupMap as $groupId => $groupData):
                    /** @var \common\models\Group $groupInfo */
                    $groupInfo = $groupData['group'];
                    /** @var \common\models\Payment[] $payments */
                    $payments = $groupData['payments'];
                    ?>
                    <div class="well well-sm<?= $groupInfo->active == \common\models\Group::STATUS_INACTIVE ? ' text-muted' : ''; ?>">
                        <?php if ($groupInfo->active == \common\models\Group::STATUS_INACTIVE): ?>
                            <div class="row">
                                <div class="col-xs-12"><small>Занятия в этой группе больше не проводятся.</small></div>
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">Группа: <b><?=$groupInfo->name; ?></b></div>
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">Предмет: <b><?=$groupInfo->subject->name; ?></b></div>
                            <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">Занятия проводит: <b><?=$groupInfo->teacher->name; ?></b></div>
                        </div>
                        <?php if ($groupInfo->scheduleData): ?>
                            <div class="row">
                                <div class="col-xs-12">Занятия проводятся:
                                    <i>
                                        <?php foreach ($groupInfo->scheduleData as $day => $time):
                                            if ($time): ?>
                                                <?= Calendar::$weekDays[$day + 1]; ?> <?= $time; ?>
                                            <?php endif;
                                        endforeach; ?>
                                    </i>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (count($payments)): ?>
                            <div class="row">
                                <div class="col-xs-12">
                                    Оплата занятий в группе:
                                    <?php foreach ($payments as $payment): ?>
                                        <b><?= $payment->amount * (-1); ?></b>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row schedule-table">
        <?php
        $currentDay = new \DateTime($eventMonth->format('Y-m') . '-01 00:00:00');
        $currentMonth = $eventMonth->format('m');

        $marks = [];
        $weekDay = intval($currentDay->format('N'));
        $offset = '';
        if ($weekDay < 7) {
            if ($weekDay % 2 == 0) $offset .= ' col-xs-offset-6';
            $offset .= ' col-sm-offset-' . (4 * (($weekDay - 1) % 3));
            $offset .= ' col-md-offset-' . (($weekDay - 1) * 2);
        }
        while ($currentDay->format('m') == $currentMonth) {
            $weekDay = intval($currentDay->format('N'));
            if ($weekDay == 7) {$currentDay->add($intervalDay); continue;}
            $key = $currentDay->format('Y-m-d');
            ?>
            <div class="col-xs-6 col-sm-4 col-md-2 <?= $offset; ?>">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <b><?= $currentDay->format('d.m.Y'); ?></b><br>
                        <small><?= Calendar::$weekDays[$currentDay->format('w')]; ?></small>
                    </div>
                </div>

                <?php if (isset($eventMap[$key])):
                    ksort($eventMap[$key]);
                    foreach ($eventMap[$key] as $eventMember): ?>
                        <div class="row event_<?= $eventMember->event->id; ?> input-group">
                            <div class="input-group-addon"><small><?= $eventMember->event->eventTime; ?></small></div>
                            <?php $eventName = $eventMember->event->group->name; ?>
                            <div class="form-control input-sm" title="<?= $eventName; ?>">
                                <?= mb_strlen($eventName, 'UTF-8') > 12
                                    ? mb_substr($eventName, 0, 12, 'UTF-8') . '...'
                                    : $eventName; ?>
                            </div>
                        </div>
                        <div class="row input-group">
                            <span class="input-group-addon"><span class="icon icon-book"></span></span>
                            <span class="form-control input-sm" title="<?= $eventMember->event->subject->name; ?>">
                                <?= mb_strlen($eventMember->event->group->subject->name, 'UTF-8') > 15
                                    ? mb_substr($eventMember->event->group->subject->name, 0, 15, 'UTF-8') . '...'
                                    : $eventMember->event->subject->name; ?>
                            </span>
                        </div>
                        <div class="row input-group">
                            <span class="input-group-addon"><span class="icon icon-user-tie"></span></span>
                            <span class="form-control input-sm" title="<?= $eventMember->event->teacher->name; ?>">
                                <?= mb_strlen($eventMember->event->teacher->shortName, 'UTF-8') > 15
                                    ? mb_substr($eventMember->event->teacher->shortName, 0, 15, 'UTF-8') . '...'
                                    : $eventMember->event->teacher->shortName; ?>
                            </span>
                        </div>
                        <?php if ($eventMember->status != \backend\models\EventMember::STATUS_UNKNOWN):
                            if ($eventMember->mark) $marks[] = $eventMember->mark;
                            if ($eventMember->event->status == \backend\models\Event::STATUS_CANCELED): ?>
                                <div class="row bg-warning"><div class="col-xs-12">Отменено</div></div>
                            <?php else: ?>
                                <div class="row <?= $eventMember->status == \backend\models\EventMember::STATUS_ATTEND ? 'bg-success' : 'bg-danger'; ?>">
                                    <div class="col-xs-8"><small><?= $eventMember->status == \backend\models\EventMember::STATUS_ATTEND ? 'Присутствовал(а)' : 'Отсутствовал(а)'; ?></small></div>
                                    <div class="col-xs-4 text-center"><b><?= $eventMember->mark ?: ''; ?></b></div>
                                </div>
                        <?php endif;
                        endif; ?>
                        <hr class="thin">
                    <?php endforeach;
                endif; ?>
            </div>
            <?php if ($weekDay == 6) { ?><div class="clearfix"></div><hr class="thin"><?php }
            elseif ($weekDay % 2 == 0) { ?>
                <div class="clearfix visible-xs"></div>
                <hr class="thin visible-xs"><?php
            } elseif ($weekDay == 3) { ?>
                <div class="clearfix visible-sm"></div>
                <hr class="thin visible-sm"><?php
            }
            $currentDay->add($intervalDay);
            $offset = '';
        }
        ?>
    </div>
    <?php if (!empty($marks)): ?>
        <div class="row">
            <div class="col-xs-12">
                <div class="alert alert-info">
                    <div class="pull-left big-font">Средний балл за месяц</div>
                    <b class="pull-right big-font"><i><?= round(array_sum($marks) / count($marks), 2); ?></i></b>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php $eventMonth->add($intervalMonth); ?>
    <div class="row">
        <div class="col-xs-12">
            <a class="btn btn-default full-width" href="<?= \yii\helpers\Url::to(array_merge($queryParams, ['schedule', 'month' => $eventMonth->format('Y-m')])); ?>">
                <span class="glyphicon glyphicon-menu-down"></span> <?= Calendar::$monthNames[$eventMonth->format('n')]; ?> <?= $eventMonth->format('Y'); ?>
            </a>
        </div>
    </div>
</div>
