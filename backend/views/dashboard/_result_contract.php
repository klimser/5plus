<?php

/* @var $this yii\web\View */
/* @var $contract \common\models\Contract */

use yii\helpers\Url;
use common\components\helpers\MoneyHelper;

?>

<div class="card">
    <h5 class="card-header" <?= $contract->isNew() ? 'info' : 'default'; ?>>
        договор <?= $contract->number; ?>
    </h5>
    <div class="card-body">
        <div class="row">
            <div class="col-9 col-lg-10">
                <table class="small table table-sm">
                    <tr><td>Студент</td><td><b><?= $contract->user->name; ?></b></td></tr>
                    <tr><td>Группа</td><td><b><?= $contract->courseConfig->name; ?></b></td></tr>
                    <tr><td>Сумма</td><td><b><?= MoneyHelper::formatThousands($contract->amount); ?></b> <?= $contract->discount ? '' : ' <span class="label label-danger">повышенная стоимость занятий</span>'; ?></td></tr>
                    <tr><td>Дата договора</td><td><b><?= $contract->createDate->format('d.m.Y'); ?></b></td></tr>
                    <?php if ($contract->isPaid()): ?>
                        <tr class="small bg-success"><td>Оплачен</td><td><b><?= $contract->paidDate->format('d.m.Y'); ?></b></td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="col-3 col-lg-2 text-right">
                <a href="<?= Url::to(['contract/print', 'id' => $contract->id]); ?>" target="_blank" title="Печать" class="btn btn-outline-dark mb-2"><span class="fas fa-print"></span></a>
                <?php if ($contract->isNew()): ?>
                    <button type="button" onclick="Dashboard.showContractForm(this);" class="btn btn-success ml-2 mb-2" data-id="<?= $contract->id; ?>" data-student-name="<?= $contract->user->name; ?>"
                            data-course-name="<?= $contract->courseConfig->name; ?>" data-amount="<?= $contract->amount; ?>" data-create-date="<?= $contract->createDate->format('d.m.Y'); ?>"
                            data-course-date-start="<?= $contract->course->startDateObject->format('d.m.Y'); ?>" data-course-student="<?= $contract->activeCourseStudent ? 1 : 0; ?>"
                        <?php if ($contract->activeCourseStudent): ?>
                            data-student-date-start="<?= $contract->activeCourseStudent->startDateObject->format('d.m.Y'); ?>"
                            data-student-date-charge="<?= $contract->activeCourseStudent->chargeDateObject ? $contract->activeCourseStudent->chargeDateObject->format('d.m.Y') : ''; ?>"
                        <?php endif; ?>
                    ><span class="fas fa-dollar-sign"></span></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

