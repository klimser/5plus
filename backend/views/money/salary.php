<?php

use yii\bootstrap4\Html;
use \common\components\helpers\Calendar;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $salaryMap array */
/* @var $date \DateTimeImmutable */

$this->title = 'Зарплата';
$this->params['breadcrumbs'][] = $this->title;
$prevMonth = $date->modify('-1 month');
$nextMonth = $date->modify('+1 month');
?>

<h1>
    <a href="<?= Url::to(['salary', 'year' => $prevMonth->format('Y'), 'month' => $prevMonth->format('n')]); ?>"
    ><span class="fas fa-arrow-left"></span></a>
    <?= Html::encode($this->title) ?> <?= Calendar::$monthNames[$date->format('n')]; ?> <?= $date->format('Y'); ?>
    <a href="<?= Url::to(['salary-details', 'year' => $date->format('Y'), 'month' => $date->format('n')]); ?>"><span class="fas fa-file"></span></a>
    <a href="<?= Url::to(['salary-details', 'year' => $date->format('Y'), 'month' => $date->format('n'), 'detail' => 1]); ?>"><span class="fas fa-file-invoice"></span></a>
    <a href="<?= Url::to(['salary', 'year' => $nextMonth->format('Y'), 'month' => $nextMonth->format('n')]); ?>"
    ><span class="fas fa-arrow-right"></span></a>
</h1>

<?php foreach ($salaryMap ?? [] as $teacherId => $courses):
    $titleRendered = false;
    $totalSalary = 0;
    foreach ($courses as $courseId => $salaryData):
        if (!$titleRendered): ?>
        <div class="card border-info mb-3">
            <h3 class="card-header text-white bg-info">
                <?= $salaryData['teacher']; ?>
            </h3>
            <div class="card-body">
                <div class="row">
                    <div class="col-8 font-weight-bold">Группа</div>
                    <div class="col-4 text-right font-weight-bold">Оплата</div>
                </div>
        <?php
            $titleRendered = true;
        endif;

        $totalSalary += $salaryData['amount']; ?>
                    <div class="row border-bottom">
                        <div class="col-8">
                            <?= $salaryData['course']; ?>
                            <a href="<?= Url::to(['salary-details', 'course' => $courseId, 'year' => $date->format('Y'), 'month' => $date->format('n')]); ?>"><span class="fas fa-file"></span></a></div>
                        <div class="col-4 text-right">
                            <?= number_format($salaryData['amount'], 0, '.', ' '); ?>
                        </div>
                    </div>
    <?php endforeach; ?>
                <div class="row">
                    <div class="col-8 font-weight-bold">Итого</div>
                    <div class="col-4 text-right"><?= number_format($totalSalary, 0, '.', ' '); ?></div>
                </div>
            </div>
        </div>
<?php endforeach; ?>
