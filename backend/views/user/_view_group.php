<?php
/* @var $this yii\web\View */

use common\components\helpers\MoneyHelper;
use common\components\helpers\WordForm;
use common\models\GroupPupil;

/* @var $pupil \common\models\User */
/* @var $contractAllowed bool */
/* @var $incomeAllowed bool */
/* @var $debtAllowed bool */
/* @var $groupManagementAllowed bool */
/* @var $moveMoneyAllowed bool */
$activeGroupIdSet = [];
foreach ($pupil->groupPupilsAggregated as $groupId => $groupPupils) {
    foreach ($groupPupils as $groupPupil) {
        if ($groupPupil->active === GroupPupil::STATUS_ACTIVE) {
            $activeGroupIdSet[$groupId] = true;
        }
    }
}
?>
<div class="groups">
    <?php if ($pupil->id): ?>
        <div class="text-right form-check mb-2">
            <input class="form-check-input filter-type" type="checkbox" value="1" onchange="Dashboard.filterGroups(this);" id="filter-type-<?= $pupil->id; ?>">
            <label class="form-check-label" for="filter-type-<?= $pupil->id; ?>">
                показать завершенные
            </label>
        </div>
    <?php endif; ?>
    <div class="groups-table">
        <?php
        /** @var GroupPupil[] $groupPupils */
        foreach ($pupil->groupPupilsAggregated as $groupId => $groupPupils):
            $groupNameRendered = false;
            $isActive = false;
            foreach ($groupPupils as $groupPupil):
                if ($groupPupil->active === GroupPupil::STATUS_ACTIVE) {
                    $isActive = true;
                }
            ?>
                <div class="row justify-content-between align-items-start border-bottom pb-3 mb-3 collapse group-item <?= $groupPupil->active === GroupPupil::STATUS_INACTIVE ? ' inactive table-secondary ' : ' show '; ?>">
                    <div class="col-8 col-md-9">
                        <div class="row">
                            <div class="col-12 col-md-4 col-lg-6">
                                <?php if (!$groupNameRendered): ?>
                                    <?= $groupPupil->group->name; ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 col-md-4 col-lg-3">
                                с <?= $groupPupil->startDateObject->format('d.m.Y') ;?>
                                <?php if ($groupPupil->date_end): ?>
                                    <br> до <?= $groupPupil->endDateObject->format('d.m.Y'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 col-md-4 col-lg-3">
                                <?php if (!$groupNameRendered):
                                    $moneyLeft = $groupPupil->moneyLeft; ?>
                                    <?= $moneyLeft < 0 ? 'долг ' : ''; ?>
                                    <span class="badge badge-<?= $moneyLeft < 0 ? 'danger' : 'success'; ?>"><?= MoneyHelper::formatThousands(abs($moneyLeft)); ?></span><br>
                                    
                                    <?php if ($groupPupil->paid_lessons >= 0): ?>
                                        <b><?= $groupPupil->paid_lessons; ?></b> <?= WordForm::getLessonsForm($groupPupil->paid_lessons); ?><br>
                                        до <i><?= $groupPupil->chargeDateObject->format('d.m.Y'); ?></i>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 col-md-3 text-right">
                        <?php if ($groupPupil->active === GroupPupil::STATUS_ACTIVE): ?>
                            <?php if ($incomeAllowed): ?>
                                <?php if ($pupil->isAgeConfirmed()): ?>
                                    <button type="button" title="принять оплату" class="btn btn-primary mb-2" onclick="Dashboard.showMoneyIncomeForm(this);"
                                            data-group="<?= $groupPupil->group_id; ?>" data-user="<?= $pupil->id; ?>">
                                        <span class="fas fa-dollar-sign"></span>
                                    </button>
                                <?php else: ?>
                                    <button type="button" title="отправить СМС для подтверждения возраста" class="btn btn-primary mb-2"
                                            onclick="Dashboard.showAgeConfirmationForm(this);" data-user="<?= $pupil->id; ?>"
                                            data-phone1="<?= $pupil->phone; ?>" data-phone2="<?= $pupil->phone2; ?>"
                                            data-phone3="<?= $pupil->parent_id ? $pupil->parent->phone : ''; ?>"
                                            data-phone4="<?= $pupil->parent_id ? $pupil->parent->phone2 : ''; ?>">
                                        <span class="fas fa-baby"></span>
                                    </button>
                                    <?php if ($debtAllowed): ?>
                                        <button type="button" title="принять оплату" class="btn btn-primary mb-2" onclick="if (confirm('Are you sure?')) Dashboard.showMoneyIncomeForm(this);"
                                                data-group="<?= $groupPupil->group_id; ?>" data-user="<?= $pupil->id; ?>">
                                            <span class="fas fa-dollar-sign"></span>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        
                            <?php if ($debtAllowed): ?>
                                <button type="button" title="добавить долг" class="btn btn-outline-danger mb-2" onclick="Dashboard.showMoneyDebtForm(this);"
                                        data-group="<?= $groupPupil->group_id; ?>" data-user="<?= $pupil->id; ?>">
                                    <span class="fas fa-cash-register"></span>
                                </button>
                            <?php endif; ?>
        
                            <?php if ($incomeAllowed): ?>
                                <button type="button" title="выдать договор" class="btn btn-outline-dark mb-2" onclick="Dashboard.showNewContractForm(this);"
                                        data-group="<?= $groupPupil->group_id; ?>" data-user="<?= $pupil->id; ?>">
                                    <span class="fas fa-file-contract"></span>
                                </button>
                            <?php endif; ?>
        
                            <?php if ($groupManagementAllowed): ?>
                                <button type="button" title="перевести в другую группу" class="btn btn-outline-dark mb-2" onclick="Dashboard.showMovePupilForm(this);"
                                    data-id="<?= $groupPupil->id; ?>" data-group="<?= $groupPupil->group_id; ?>"
                                    data-date="<?= $groupPupil->startDateObject->format('d.m.Y'); ?>">
                                    <span class="fas fa-running"></span> <span class="fas fa-arrow-right"></span>
                                </button>
                                <button type="button" title="завершает ходить" class="btn btn-outline-dark mb-2" onclick="Dashboard.showEndPupilForm(this);"
                                        data-id="<?= $groupPupil->id; ?>" data-group="<?= $groupPupil->group_id; ?>"
                                        data-date="<?= $groupPupil->startDateObject->format('Y-m-d'); ?>">
                                    <span class="fas fa-skull-crossbones"></span>
                                </button>
                            <?php endif; ?>
                        <?php elseif (!$isActive && $moveMoneyAllowed && $groupPupil->moneyLeft > 0): ?>
                            <button type="button" title="перенести оставшиеся деньги" class="btn btn-outline-dark mb-2" onclick="Dashboard.showMoveMoneyForm(this);"
                                data-id="<?= $groupPupil->id; ?>" data-group="<?= $groupPupil->group_id; ?>" data-amount="<?= MoneyHelper::formatThousands($groupPupil->moneyLeft); ?>"
                                data-groups="<?= implode(',', array_keys($activeGroupIdSet)); ?>">
                                <span class="fas fa-dollar-sign"></span> <span class="fas fa-arrow-right"></span>
                            </button>
                        <?php endif; ?>

                        <?php if (!$isActive && $debtAllowed && $groupPupil->moneyLeft > 0): ?>
                            <button type="button" title="возврат" class="btn btn-outline-danger mb-2" onclick="Dashboard.showMoneyDebtForm(this, true);"
                                    data-group="<?= $groupPupil->group_id; ?>" data-user="<?= $pupil->id; ?>" data-amount="<?= MoneyHelper::formatThousands($groupPupil->moneyLeft); ?>">
                                <span class="fas fa-search-dollar"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
                if ($groupPupil->active === GroupPupil::STATUS_ACTIVE) {
                    $groupNameRendered = true;
                }
            endforeach;
        endforeach; ?>
    </div>
</div>
<button type="button" class="btn btn-success" onclick="User.addGroup(undefined, $(this).closest('.user-view'));"><span class="fas fa-plus"></span> добавить</button>
