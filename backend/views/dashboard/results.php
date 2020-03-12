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
<div class="panel panel-default">
    <div class="panel-body">
        <p>Предмет <b><?= $giftCard->name; ?></b></p>
        <p>Сумма <b><?= Money::formatThousands($giftCard->amount); ?></b></p>
        <?php if ($giftCard->isNew()): ?>
            <span class="label label-danger">не оплачена</span>
        <?php endif; ?>

        <?php if ($giftCard->isUsed()): ?>
            <span class="label label-success">использована</span> <?= $giftCard->usedDate->format('d.m.Y'); ?>
        <?php endif; ?>

        <?php if ($giftCard->isPaid()): ?>
            <p>куплена <b><?= $giftCard->paidDate->format('d.m.Y'); ?></b></p>
            <form id="gift-card-form" onsubmit="return Money.completeGiftCard(this);">
                <input type="hidden" name="gift_card_id" value="<?= $giftCard->id; ?>" required>
                <input type="hidden" name="pupil[id]" value="<?= $existingPupil ? $existingPupil->id : ''; ?>">
                <input type="hidden" name="group[existing]" id="existing_group_id">
                <div class="row">
                    <div class="col-xs-12 col-sm-6">
                        <div class="form-group">
                            <label for="pupil_name">ФИО студента</label>
                            <input id="pupil_name" class="form-control" name="pupil[name]" required
                                   <?php if ($existingPupil): ?> disabled value="<?= $existingPupil->name; ?>"
                                   <?php else: ?> value="<?= $giftCard->customer_name; ?>" <?php endif; ?> >
                        </div>
                        <div class="form-group">
                            <label for="pupil_phone">Телефон студента</label>
                            <div class="input-group">
                                <span class="input-group-addon">+998</span>
                                <input id="pupil_phone" class="form-control phone-formatted"
                                       name="pupil[phoneFormatted]" required maxlength="11" pattern="\d{2} \d{3}-\d{4}"
                                       <?php if ($existingPupil): ?> disabled value="<?= $existingPupil->phoneFormatted; ?>"
                                       <?php else: ?> value="<?= $giftCard->phoneFormatted; ?>" <?php endif; ?> >
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6">
                        <div class="form-group">
                            <label for="parents_name">ФИО родителей</label>
                            <input id="parents_name" class="form-control" name="parents[name]"
                                <?php if ($existingPupil): ?> disabled value="<?= $existingPupil->parent_id ? $existingPupil->parent->name : ''; ?>"
                                <?php else: ?> value="<?= $giftCard->additionalData['parents_name'] ?? ''; ?>" <?php endif; ?>>
                        </div>
                        <div class="form-group">
                            <label for="parents_phone">Телефон родителей</label>
                            <div class="input-group">
                                <span class="input-group-addon">+998</span>
                                <input id="parents_phone" class="form-control phone-formatted" name="parents[phoneFormatted]"
                                       maxlength="11" pattern="\d{2} \d{3}-\d{4}"
                                       <?php if ($existingPupil): ?> disabled value="<?= $existingPupil->parent_id ? $existingPupil->parent->phoneFormatted : ''; ?>"
                                       <?php else: ?> value="<?= $giftCard->additionalData['parents_phone'] ?? ''; ?>" <?php endif; ?>>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <?php if ($existingPupil):
                    foreach ($existingPupil->activeGroupPupils as $groupPupil): ?>
                        <button class="btn btn-default btn-lg margin-right-10 gift-card-existing-group" type="button"
                                data-group="<?= $groupPupil->id; ?>" onclick="Money.setGiftGroup(this);">
                            <?= $groupPupil->group->name; ?> с <?= $groupPupil->startDateObject->format('d.m.Y'); ?>
                        </button>
                        <br>
                    <?php endforeach;
                endif; ?>
                <div class="row">
                    <div class="col-xs-12 col-sm-6">
                        <div class="form-group">
                            <label for="new-group">Добавить в новую группу</label>
                            <div class="input-group">
                                <select id="new-group" name="group[id]" class="form-control"></select>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6">
                        <div class="form-group">
                            <label for="new-group-date">Дата начала занятий</label>
                            <div class="input-group date datepicker">
                                <input class="form-control" name="group[date]" id="new-group-date" value="<?= date('d.m.Y'); ?>" required pattern="\d{2}\.\d{2}\.\d{4}">
                                <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group text-right">
                    <button class="btn btn-primary">внести</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif;
