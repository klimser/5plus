<?php
use common\components\helpers\Money;
use common\models\Contract;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $pupil \common\models\User */
/** @var \common\models\Contract[] $payments */
$contracts = $pupil->getContracts()->with('group')->all();
?>

<div class="view-contracts">
    <table class="table table-bordered table-sm table-responsive-md">
        <tr>
            <th>группа</th>
            <th>сумма</th>
            <th>дата</th>
            <th>тип</th>
            <th>статус</th>
            <th></th>
        </tr>
        <?php foreach ($contracts as $contract): ?>
            <tr <?php if ($contract->status === Contract::STATUS_PAID): ?> class="table-success"<?php endif; ?> >
                <td><?= $contract->group->name; ?></td>
                <td class="text-right"><?= Money::formatThousands($contract->amount); ?></td>
                <td><?= $contract->createDate->format('d.m.Y'); ?></td>
                <td>
                    <?php if ($contract->payment_type): ?>
                        <?= Contract::PAYMENT_TYPE_LABELS[$contract->payment_type]; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?= Contract::STATUS_LABELS[$contract->status]; ?>
                    <?php if ($contract->status === Contract::STATUS_PAID): ?>
                        <?= $contract->paidDate->format('d.m.Y'); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($contract->status !== Contract::STATUS_PROCESS): ?>
                        <a href="<?= Url::to(['contract/print', 'id' => $contract->id]); ?>" target="_blank" title="Печать" class="btn btn-outline-dark btn-sm"><span class="fas fa-print"></span></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
