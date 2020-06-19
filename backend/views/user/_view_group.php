<?php
/* @var $this yii\web\View */

use common\components\helpers\Money;
use common\components\helpers\WordForm;
use common\models\GroupPupil;

/* @var $pupil \common\models\User */
/* @var $contractAllowed bool */
/* @var $incomeAllowed bool */
/* @var $groupManagementAllowed bool */
/* @var $moveMoneyAllowed bool */
/** @var GroupPupil[] $groupPupils */
$groupPupils = $pupil->getGroupPupils()->orderBy(['date_start' => SORT_DESC])->with('group')->all();
$activeGroupIdSet = [];
foreach ($groupPupils as $groupPupil) {
    if ($groupPupil->active === GroupPupil::STATUS_ACTIVE) {
        $activeGroupIdSet[$groupPupil->group_id] = true;
    }
}
?>
<div class="groups">
    <div class="text-right form-check mb-2">
        <input class="form-check-input filter-type" type="checkbox" value="1" onchange="Dashboard.filterGroups(this);" id="filter-type-<?= $pupil->id; ?>">
        <label class="form-check-label" for="filter-type-<?= $pupil->id; ?>">
            показать завершенные
        </label>
    </div>
    <table class="table groups-table">
        <?php foreach ($groupPupils as $groupPupil): ?>
            <tr class="collapse <?= $groupPupil->active === GroupPupil::STATUS_INACTIVE ? ' inactive ' : ' show '; ?>">
                <td><?= $groupPupil->group->name; ?></td>
                <td>
                    с <?= $groupPupil->startDateObject->format('d.m.Y') ;?>
                    <?php if ($groupPupil->date_end): ?>
                        <br> до <?= $groupPupil->endDateObject->format('d.m.Y'); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $moneyLeft = $groupPupil->moneyLeft; ?>
                    <?= $moneyLeft < 0 ? 'долг ' : ''; ?>
                    <span class="badge badge-<?= $moneyLeft < 0 ? 'danger' : 'success'; ?>"><?= Money::formatThousands(abs($moneyLeft)); ?></span><br>
                    
                    <?php if ($groupPupil->paid_lessons >= 0): ?>
                        <b><?= $groupPupil->paid_lessons; ?></b> <?= WordForm::getLessonsForm($groupPupil->paid_lessons); ?><br>
                        до <i><?= $groupPupil->chargeDateObject->format('d.m.Y'); ?></i>
                    <?php endif; ?>
                </td>
                <td class="text-right">
                    <?php if ($groupPupil->active === GroupPupil::STATUS_ACTIVE): ?>
                        <?php if ($incomeAllowed): ?>
                            <button type="button" title="принять оплату" class="btn btn-primary" onclick="Dashboard.showMoneyIncomeForm(this);"
                                    data-group="<?= $groupPupil->group_id; ?>" data-user="<?= $pupil->id; ?>">
                                <span class="fas fa-dollar-sign"></span>
                            </button>
                        <?php endif; ?>
    
                        <?php if ($incomeAllowed): ?>
                            <button type="button" title="выдать договор" class="btn btn-outline-dark" onclick="Dashboard.showNewContractForm(this);"
                                    data-group="<?= $groupPupil->group_id; ?>" data-user="<?= $pupil->id; ?>">
                                <span class="fas fa-file-contract"></span>
                            </button>
                        <?php endif; ?>
    
                        <?php if ($groupManagementAllowed):
                            $limitDate = clone $groupPupil->startDateObject;
                            $limitDate->modify('+1 day');
                            ?>
                            <button type="button" title="перевести в другую группу" class="btn btn-outline-dark" onclick="Dashboard.showMovePupilForm(this);"
                                data-id="<?= $groupPupil->id; ?>" data-group="<?= $groupPupil->group_id; ?>"
                                data-date="<?= $limitDate->format('d.m.Y'); ?>">
                                <span class="fas fa-running"></span> <span class="fas fa-arrow-right"></span>
                            </button>
                            <button type="button" title="удалить из группы" class="btn btn-outline-dark" onclick="Dashboard.showEndPupilForm(this);"
                                    data-id="<?= $groupPupil->id; ?>" data-group="<?= $groupPupil->group_id; ?>"
                                    data-date="<?= $limitDate->format('Y-m-d'); ?>">
                                <span class="fas fa-skull-crossbones"></span>
                            </button>
                        <?php endif; ?>
                    <?php elseif ($moveMoneyAllowed && $groupPupil->moneyLeft > 0): ?>
                        <button type="button" title="перенести оставшиеся деньги" class="btn btn-outline-dark" onclick="Dashboard.showMoveMoneyForm(this);"
                            data-id="<?= $groupPupil->id; ?>" data-group="<?= $groupPupil->group_id; ?>" data-amount="<?= Money::formatThousands($groupPupil->moneyLeft); ?>"
                            data-groups="<?= implode(',', array_keys($activeGroupIdSet)); ?>">
                            <span class="fas fa-dollar-sign"></span> <span class="fas fa-arrow-right"></span>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<button type="button" class="btn btn-success" onclick="User.addGroup(undefined, $(this).closest('.user-view'));"><span class="fas fa-plus"></span> добавить</button>
