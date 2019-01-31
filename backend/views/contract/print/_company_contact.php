<?php
/* @var $contract \common\models\Contract */
/* @var $this yii\web\View */
?>
<td style="width: 50%; padding-right: 1cm; text-align: left; vertical-align: top;">
    <div style="text-decoration: underline;"><b>Учреждение:</b></div><br>

    <div style="text-decoration: underline;"><b><?= $contract->company->first_name; ?></b></div>

    <span style="text-decoration: underline;">Юридический адрес:</span> <?= $contract->company->zip; ?>, г. <?= $contract->company->city; ?>,<br>
    <?= $contract->company->address; ?>,<br>
    тел.: <?= $contract->company->phone; ?><br>
    ИНН <?= $contract->company->tin; ?>, ОКЭД <?= $contract->company->oked; ?><br>
    <?= $contract->company->bank_data; ?><br>
    МФО: <?= $contract->company->mfo; ?>
    <br><br><br>

    <div class="text-right">Директор <span class="placeholder" style="width: 4cm;"></span></div>
    <div class="text-right"><span class="text-center" style="display: inline-block; width: 5cm;">(<?= $contract->company->head_name_short; ?>)</span></div>
    М.П.
</td>
