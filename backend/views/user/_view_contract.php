<?php
use common\components\helpers\MoneyHelper;
use common\models\Contract;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $student \common\models\User */
/** @var \common\models\Contract[] $contracts */
$contracts = $student->getContracts()->with('course')->all();
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
                <td><?= $contract->courseConfig->name; ?></td>
                <td class="text-right"><?= MoneyHelper::formatThousands($contract->amount); ?></td>
                <td><?= $contract->createDate->format('d.m.Y'); ?></td>
                <td>
                    <?php if ($contract->payment_type && !in_array($contract->payment_type, Contract::MANUAL_PAYMENT_TYPES)): ?>
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
