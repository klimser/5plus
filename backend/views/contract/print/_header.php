<?php
/* @var $contract \common\models\Contract */
/* @var $this yii\web\View */
?>
<div style="float: right; width: 3cm; height: 3cm; margin: 0 0 5mm 5mm;">
    <img src="<?= \yii\helpers\Url::to(['contract/qr']); ?>" style="width: 3cm;">
</div>
<div style="margin-right: 35mm;">
<table>
    <tr>
        <td style="width: 5cm; vertical-align: top;">
            <img src="<?= \yii\helpers\Url::to(['contract/barcode', 'id' => $contract->id]); ?>" style="width: 5cm;">
        </td>
        <td style="padding-left: 5mm;">
            <table style="margin: 0;">
                <tr>
                    <td colspan="2" class="text-center" style="vertical-align: top;">
                        <b>ДОГОВОР</b> № <span class="placeholder"><?= $contract->number; ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="text-left" style="padding-top: 5mm; vertical-align: bottom;">г. Ташкент</td>
                    <td class="text-right" style="padding-top: 5mm; vertical-align: bottom;">
            <span class="placeholder">
                «<?= $contract->createDate->format('d'); ?>»
                <?= \common\components\helpers\Calendar::getMonthForm2($contract->createDate->format('n')); ?>
                <?= $contract->createDate->format('Y'); ?> г.
            </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</div>
