<?php
/* @var $contract \common\models\Contract */
/* @var $this yii\web\View */
?>
<td style="width: 50%; padding-right: 1cm; text-align: left; vertical-align: top;">
    <div class="text-center text-uppercase"><b>Учреждение</b></div>

    <div class="text-center"><b><?= $contract->company->first_name; ?></b></div>

    Адрес: <?= $contract->company->zip; ?>, г. <?= $contract->company->city; ?>,<br>
    <?= $contract->company->address; ?>,<br>
    тел.: <?= $contract->company->phone; ?><br>
    ИНН <?= $contract->company->tin; ?>, ОКЭД <?= $contract->company->oked; ?><br>
    <?= $contract->company->bank_data; ?><br>
    Код банка: <?= $contract->company->mfo; ?>
    <br><br><br>

    <div class="text-right"><b>Директор <span class="placeholder" style="width: 4cm;"></span> <?= $contract->company->head_name_short; ?></b></div>
</td>
