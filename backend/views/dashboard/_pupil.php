<?php
/** @var $pupil \common\models\User */
?>

<div class="panel panel-success result-pupil">
    <div class="panel-heading">
        <span class="label label-success">студент</span>
        <span class="pupil-name"><?= $pupil->name; ?></span>
        <a class="btn btn-default pull-right pupil-info-button" target="_blank" href="<?= \yii\helpers\Url::to(['user/view', 'id' => $pupil->id]); ?>">
            <span class="fas fa-info-circle"></span>
        </a>
    </div>
    <table class="table table-condensed">
        <?php foreach ($pupil->activeGroupPupils as $groupPupil): ?>
            <tr>
                <td><?= $groupPupil->group->name; ?> (с
                    <?= $groupPupil->startDateObject->format('d.m.Y') ;?><?= $groupPupil->date_end
                        ? 'до ' . $groupPupil->endDateObject->format('d.m.Y') : ''; ?>)</td>
                <td>
                    <?php if ($groupPupil->paid_lessons >= 0): ?>
                        оплачено до <span class="label label-success"><?= $groupPupil->chargeDateObject->format('d.m.Y'); ?></span>
                    <?php else: ?>
                        долг <span class="label label-danger"><?= $groupPupil->paid_lessons * (-1); ?> <?= \common\components\helpers\WordForm::getLessonsForm($groupPupil->paid_lessons); ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-right">
                    <button class="btn btn-primary" onclick="Dashboard.launchMoneyIncome(this);"
                            data-group="<?= $groupPupil->group_id; ?>" data-user="<?= $pupil->id; ?>">
                        <span class="fas fa-dollar-sign"></span>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
