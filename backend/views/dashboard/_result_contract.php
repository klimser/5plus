<?php

/* @var $this yii\web\View */

use yii\helpers\Url;
use common\components\helpers\Money;

/* @var $contract \common\models\Contract */

?>

<div class="panel panel-<?= $contract->isNew() ? 'info' : 'default'; ?>">
    <div class="panel-heading">
        договор <?= $contract->number; ?>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-xs-12 col-sm-8 col-md-10">
                <table class="small table table-condensed">
                    <tr><td>Студент</td><td><b><?= $contract->user->name; ?></b></td></tr>
                    <tr><td>Группа</td><td><b><?= $contract->group->name; ?></b></td></tr>
                    <tr><td>Сумма</td><td><b><?= Money::formatThousands($contract->amount); ?></b> <?= $contract->discount ? ' <span class="label label-success">со скидкой</span>' : ''; ?></td></tr>
                    <tr><td>Дата договора</td><td><b><?= $contract->createDate->format('d.m.Y'); ?></b></td></tr>
                    <?php if ($contract->isPaid()): ?>
                        <tr class="small bg-success"><td>Оплачен</td><td><b><?= $contract->paidDate->format('d.m.Y'); ?></b></td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="col-xs-12 col-sm-4 col-md-2 text-right">
                <a href="<?= Url::to(['contract/print', 'id' => $contract->id]); ?>" target="_blank" title="Печать" class="btn btn-default"><span class="fas fa-print"></span></a>
                <?php if ($contract->isNew()): ?>
                    <button type="button" onclick="Dashboard.showContractForm(this);" class="btn btn-success" data-id="<?= $contract->id; ?>" data-pupil-name="<?= $contract->user->name; ?>"
                            data-group-name="<?= $contract->group->name; ?>" data-amount="<?= $contract->amount; ?>" data-create-date="<?= $contract->createDate->format('d.m.Y'); ?>"
                            data-group-date-start="<?= $contract->group->startDateObject->format('d.m.Y'); ?>" data-group-pupil="<?= $contract->activeGroupPupil ? 1 : 0; ?>"
                        <?php if ($contract->activeGroupPupil): ?>
                            data-pupil-date-start="<?= $contract->activeGroupPupil->startDateObject->format('d.m.Y'); ?>"
                            data-pupil-date-charge="<?= $contract->activeGroupPupil->chargeDateObject ? $contract->activeGroupPupil->chargeDateObject->format('d.m.Y') : ''; ?>"
                        <?php endif; ?>
                    ><span class="fas fa-dollar-sign"></span></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

