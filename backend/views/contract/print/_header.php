<?php
/* @var $contract \common\models\Contract */
/* @var $this yii\web\View */
?>

<table>
    <tr>
        <td style="width: 5cm;" rowspan="3">
            <img src="<?= \yii\helpers\Url::to(['contract/barcode', 'id' => $contract->id]); ?>" style="width: 5cm; margin-right: 3mm;">
        </td>
    </tr>
    <tr>
        <td class="text-center" colspan="2" style="padding-right: 5cm;"><b>ДОГОВОР</b></td>
    </tr>
    <tr>
        <td class="text-left">г. Ташкент</td>
        <td class="text-right">
            <span class="placeholder">
                «<?= $contract->createDate->format('d'); ?>»
                <?= \common\components\helpers\Calendar::getMonthForm2($contract->createDate->format('n')); ?>
                <?= $contract->createDate->format('Y'); ?> г.
            </span>
            № <span class="placeholder"><?= $contract->number; ?></span>
        </td>
    </tr>
</table>