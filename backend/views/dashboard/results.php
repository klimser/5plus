<?php

/* @var $this yii\web\View */

use yii\helpers\Url;

/* @var $contract \common\models\Contract */
/* @var $giftCard \common\models\GiftCard */
/* @var $parents \common\models\User[] */
/* @var $pupils \common\models\User[] */

$noResults = true;

if ($contract): 
    $noResults = false; ?>
<div class="panel panel-default">
    <div class="panel-body">
        <table class="small pull-left">
            <tr><td><b>Студент</b></td><td><?= $contract->user->name; ?></td></tr>
            <tr><td><b>Группа</b></td><td><?= $contract->group->name; ?></td></tr>
            <tr><td><b>Сумма</b></td><td><?= $contract->amount; ?> <?= $contract->discount ? ' <span class="label label-success">со скидкой</span>' : ''; ?></td></tr>
            <tr><td><b>Дата договора</b></td><td><?= $contract->createDate->format('d.m.Y'); ?></td></tr>
            <?php if ($contract->isPaid()): ?>
                <tr class="small bg-success"><td><b>Оплачен</b></td><td><?= $contract->paidDate->format('d.m.Y'); ?></td></tr>
            <?php endif; ?>
        </table>
        <div class="pull-right">
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
        <div class="clearfix"></div>
    </div>
</div>
<?php endif;
