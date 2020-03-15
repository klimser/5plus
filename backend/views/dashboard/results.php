<?php

/* @var $this yii\web\View */

use yii\helpers\Url;
use common\components\helpers\Money;

/* @var $contract \common\models\Contract */
/* @var $giftCard \common\models\GiftCard */
/* @var $existingPupil \common\models\User */
/* @var $parents \common\models\User[] */
/* @var $pupils \common\models\User[] */

$noResults = true;

if ($contract): 
    $noResults = false; ?>
<div class="panel panel-default">
    <div class="panel-body">
        <table class="small pull-left">
            <tr><td>Студент</td><td><b><?= $contract->user->name; ?></b></td></tr>
            <tr><td>Группа</td><td><b><?= $contract->group->name; ?></b></td></tr>
            <tr><td>Сумма</td><td><b><?= Money::formatThousands($contract->amount); ?></b> <?= $contract->discount ? ' <span class="label label-success">со скидкой</span>' : ''; ?></td></tr>
            <tr><td>Дата договора</td><td><b><?= $contract->createDate->format('d.m.Y'); ?></b></td></tr>
            <?php if ($contract->isPaid()): ?>
                <tr class="small bg-success"><td>Оплачен</td><td><b><?= $contract->paidDate->format('d.m.Y'); ?></b></td></tr>
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

if ($giftCard):
    $noResults = false; ?>
    <?= $this->render('_gift_card', ['giftCard' => $giftCard, 'existingPupil' => $existingPupil]); ?>
<?php endif;

if ($parents):
    $noResults = false;
    foreach ($parents as $parent): ?>
    <div class="panel panel-info result-parent">
        <div class="panel-heading">
            <span class="label label-info"><?= $parent->role === \common\models\User::ROLE_PARENTS ? 'родитель' : 'компания'; ?></span>
            <?= $parent->name; ?>
        </div>
        <div class="panel-body">
            <div class="pull-left">
                <?php foreach ($parent->children as $child): ?>
                    <?= $child->name; ?><br>
                <?php endforeach; ?>
            </div>
            <div class="pull-right">
                <button class="btn btn-default" onclick="Dashboard.toggleChildren(this);">
                    <span class="fas fa-arrow-alt-circle-down"></span>
                </button>
            </div>
            <div class="clearfix"></div>
            <div class="children-list hidden">
                <br>
                <?php foreach ($parent->children as $child): ?>
                    <?= $this->render('_pupil', ['pupil' => $child]); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endforeach;
endif;

if ($pupils):
    $noResults = false;
    foreach ($pupils as $pupil): ?>
        <?= $this->render('_pupil', ['pupil' => $pupil]); ?>
<?php endforeach;
endif;

if ($noResults): ?>
<div class="alert alert-warning">
    Ничего не найдено
</div>
<?php endif; ?>