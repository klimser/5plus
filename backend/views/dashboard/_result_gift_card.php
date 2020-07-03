<?php
use common\components\helpers\MoneyHelper;
use common\models\User;
use yii\bootstrap4\Html;

/** @var $giftCard \common\models\GiftCard */
/** @var $existingPupil User|null */
?>
<div class="card">
    <div class="card-header">
        Предоплаченная карта
    </div>
    <div class="card-body">
        <p>Предмет <b><?= $giftCard->name; ?></b></p>
        <p>Сумма <b><?= MoneyHelper::formatThousands($giftCard->amount); ?></b></p>
        <?php if ($giftCard->isNew()): ?>
            <span class="label label-danger">не оплачена</span>
        <?php endif; ?>

        <?php if ($giftCard->isUsed()): ?>
            <span class="label label-success">использована</span> <?= $giftCard->usedDate->format('d.m.Y'); ?>
        <?php endif; ?>

        <?php if ($giftCard->isPaid()): ?>
            <p>куплена <b><?= $giftCard->paidDate->format('d.m.Y'); ?></b></p>
            <form id="gift-card-form" onsubmit="Dashboard.completeGiftCard(this); return false;">
                <input type="hidden" name="gift_card_id" value="<?= $giftCard->id; ?>" required>
                <input type="hidden" name="pupil[id]" value="<?= $existingPupil ? $existingPupil->id : ''; ?>">
                <input type="hidden" name="group[existing]" id="existing_group_id">
                <div class="row">
                    <div class="col-12 col-sm-6">
                        <?php $nameDiffer = ($existingPupil && $existingPupil->name !== $giftCard->customer_name); ?>
                        <div class="form-group <?= $nameDiffer ? ' has-error ' : ''; ?>">
                            <label for="pupil_name">ФИО студента</label>
                            <input id="pupil_name" class="form-control" name="pupil[name]" required
                                <?php if ($existingPupil): ?> disabled value="<?= $existingPupil->name; ?>"
                                <?php else: ?> value="<?= $giftCard->customer_name; ?>" <?php endif; ?> >
                            <?php if ($nameDiffer): ?>
                                <span class="help-block">Имя покупателя <b><?= $giftCard->customer_name; ?></b> отличается от имени студента с этим номером телефона! Проверьте!</span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="pupil_phone">Телефон студента</label>
                            <div class="input-group">
                                <span class="input-group-prepend">
                                    <span class="input-group-text">+998</span>
                                </span>
                                <input id="pupil_phone" class="form-control phone-formatted"
                                       name="pupil[phoneFormatted]" required maxlength="11" pattern="\d{2} \d{3}-\d{4}"
                                    <?php if ($existingPupil): ?> disabled value="<?= $existingPupil->phoneFormatted; ?>"
                                    <?php else: ?> value="<?= $giftCard->phoneFormatted; ?>" <?php endif; ?> >
                            </div>
                        </div>
                        <?= Html::radioList(
                            'person_type',
                            User::ROLE_PARENTS,
                            [
                                User::ROLE_PARENTS => 'Физ. лицо',
                                User::ROLE_COMPANY => 'Юр. лицо',
                            ]
                        ); ?>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="parents_name">ФИО родителей / Название компании</label>
                            <input id="parents_name" class="form-control" name="parents[name]"
                                <?php if ($existingPupil && $existingPupil->parent_id): ?> disabled value="<?= $existingPupil->parent->name; ?>"
                                <?php else: ?> value="<?= $giftCard->additionalData['parents_name'] ?? ''; ?>" <?php endif; ?>>
                        </div>
                        <div class="form-group">
                            <label for="parents_phone">Телефон родителей (компании)</label>
                            <div class="input-group">
                                <span class="input-group-prepend">
                                    <span class="input-group-text">+998</span>
                                </span>
                                <input id="parents_phone" class="form-control phone-formatted" name="parents[phoneFormatted]"
                                       maxlength="11" pattern="\d{2} \d{3}-\d{4}"
                                    <?php if ($existingPupil && $existingPupil->parent_id): ?> disabled value="<?= $existingPupil->parent->phoneFormatted; ?>"
                                    <?php else: ?> value="<?= $giftCard->additionalData['parents_phone'] ?? ''; ?>" <?php endif; ?>>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <?php if ($existingPupil): ?>
                    <h4>Оплатить занятия в группе</h4>
                    <?php foreach ($existingPupil->activeGroupPupils as $groupPupil): ?>
                        <button class="btn btn-default btn-lg mr-2 gift-card-existing-group" type="button"
                                data-group="<?= $groupPupil->group_id; ?>" onclick="Dashboard.setGiftGroup(this);">
                            <?= $groupPupil->group->name; ?> с <?= $groupPupil->startDateObject->format('d.m.Y'); ?>
                        </button>
                    <?php endforeach; ?>
                    <br><br>
                <?php endif; ?>
                <h4>Добавить в новую группу</h4>
                <div class="row">
                    <div class="col-12 col-sm-6 form-group">
                        <div class="input-group">
                            <select id="new-group" name="group[id]" class="form-control" onchange="Dashboard.selectGiftGroup(this);"></select>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 form-group">
                        <div class="input-group">
                            <span class="input-group-prepend">
                                <span class="input-group-text">с</span>
                            </span>
                            <input class="form-control datepicker" name="group[date]" id="new-group-date" value="<?= date('d.m.Y'); ?>" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="form-group text-right">
                    <button id="gift-button" class="btn btn-primary">внести</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
