<?php

use common\components\helpers\Calendar;
use common\components\helpers\WordForm;
use yii\helpers\Url;
use common\components\helpers\Html;

/* @var $this yii\web\View */
/* @var $course common\models\Course */

$this->title = 'Группа: ' . $course->courseConfig->name;
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $course->courseConfig->name;

?>

<div class="row pb-3">
    <div class="col-12 text-center">
        Группа <b><?= $course->courseConfig->name; ?></b> <small class="text-muted">(<?= $course->subject->name['ru']; ?>)</small>
    </div>
</div>
<div class="row border-top py-2 text-center">
    <?php for ($i = 0; $i < 7; $i++): ?>
        <div class="col">
            <div class="font-weight-bold">
                <span class="d-md-none"><?= Calendar::$weekDaysShort[($i + 1) % 7]; ?></span>
                <span class="d-none d-md-inline"><?= Calendar::$weekDays[($i + 1) % 7]; ?></span>
            </div>
            <small><?= $course->courseConfig->schedule[$i]; ?></small>
        </div>
    <?php endfor; ?>
</div>
<div class="row border-top py-2">
    <div class="col-12 col-md-6">
        Цена занятия (&lt; 12 уроков): <b><?= $course->courseConfig->lesson_price; ?></b><br>
        Цена занятия (&gt; 12 уроков): <b><?= $course->courseConfig->lesson_price_discount; ?></b>
    </div>
    <div class="col-12 col-md-6">
        Учитель <b><?= $course->teacher->name; ?></b>
        <?php if ($course->teacher->phone): ?>
            (<small class="text-muted"><?= Html::phoneLink($course->teacher->phone); ?></small>)
        <?php endif; ?>
        <?php if ($course->teacher->birthday):
            $diffDay = date_diff($course->teacher->birthdayDate, new \DateTime(), false)->days;
            if ($diffDay <= 7 && $diffDay >= 0): ?>
                <br><small class="text-muted">день рождения <?= $course->teacher->birthdayDate->format('d.m'); ?></small>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($course->courseConfig->room_number): ?>
            <br>Кабинет <b><?= $course->courseConfig->room_number; ?></b>
        <?php endif; ?>
    </div>
</div>
<div class="row border-top pt-3">
    <div class="col-12">
        <h4>Студенты:</h4>
        <?php $i = 0; $nowDate = new \DateTime(); foreach ($course->activeCourseStudents as $courseStudent): $i++; ?>
            <div class="row border-bottom py-2">
                <div class="col-8 col-lg-10">
                    <div class="row">
                        <div class="col-1 col-md-auto font-weight-bold pr-0">
                            <?= $i; ?>
                        </div>
                        <div class="col-11 col-md-auto mr-auto">
                            <a href="<?= Url::to(['money/student-report', 'userId' => $courseStudent->user_id, 'courseId' => $courseStudent->course_id]); ?>"
                               target="_blank" class="d-print-none d-none d-md-inline">
                                <span class="fas fa-file-invoice-dollar"></span>
                            </a>
                            <?= $courseStudent->user->name; ?>
                            <?php if ($courseStudent->user->parent_id): ?>
                                <span class="fas fa-user-friends point" data-toggle="tooltip" data-placement="top" data-trigger="click hover focus" data-html="true"
                                      title="<?= htmlentities($courseStudent->user->parent->name, ENT_QUOTES); ?><br>
                                      <?= $courseStudent->user->parent->phone . ($courseStudent->user->parent->phone2 ? ', ' . $courseStudent->user->parent->phone2 : ''); ?>"></span>
                            <?php endif; ?>
                            <?php if ($courseStudent->user->note): ?>
                                <br><small><?= nl2br($courseStudent->user->note); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-11 offset-1 col-md-auto offset-md-0 text-md-right">
                            <?= Html::phoneLink($courseStudent->user->phone, $courseStudent->user->phoneFormatted); ?>
                            <?php if($courseStudent->user->phone2): ?>
                                <br>
                                <?= Html::phoneLink($courseStudent->user->phone2, $courseStudent->user->phone2Formatted); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-4 col-lg-2 text-right">
                    <?php if ($courseStudent->paid_lessons < 0): ?>
                        <span class="badge badge-danger">Долг</span> <b><?= round($courseStudent->paid_lessons) * (-1); ?></b> <?= WordForm::getLessonsForm(round($courseStudent->paid_lessons) * (-1)); ?>
                    <?php else: ?>
                        <b><?= round($courseStudent->paid_lessons); ?></b> <?= WordForm::getLessonsForm(round($courseStudent->paid_lessons)); ?>
                    <?php endif; ?><br>
                    <?= number_format($courseStudent->moneyLeft, 0, '.', ' '); ?> сум
                    <?php if ($courseStudent->date_charge_till): ?>
                        <br><span class="badge badge-<?= ($courseStudent->chargeDateObject < $nowDate) ? 'danger' : 'success'; ?>">
                            до <?= $courseStudent->chargeDateObject->format('d.m.Y'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
