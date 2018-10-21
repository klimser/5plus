<?php
/* @var $contract \backend\models\Contract */
/* @var $this yii\web\View */
?>

<style>
    @page {
        size: portrait;
    }
    body {
        font-size: 3mm;
        max-width: 20cm;
    }
    table td {
        text-align: justify;
    }
    table td.number, table th.number {
        width: 1cm;
        vertical-align: top;
    }
    ul {
        padding-left: 3mm;
        margin: 1mm;
    }
</style>
<div class="page">
    <div class="text-center"><b>ДОГОВОР</b></div>
    <table>
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

    <p style="text-align: justify;">Негосударственное образовательное учреждение «EXCLUSIVE EDUCATION» (Учебный центр «Пять с Плюсом» &trade;), осуществляющее образовательную деятельность на основании лицензии <span class="placeholder"><b>NAS UZ 001.MO.0591-05</b></span> в лице директора <span class="placeholder"><b>Климова Александра Сергеевича</b></span>, действующего на основании Устава, именуемое в дальнейшем «Учреждение», с одной стороны, и <span class="placeholder"><?= $contract->user->parent->name; ?></span> <span class="placeholder" style="width: 19cm;"></span> <span class="placeholder" style="width: 19cm;"></span>, именуемый в дальнейшем «Заказчик» и <span class="placeholder"><?= $contract->user->name; ?></span>, именуемый в дальнейшем «Учащийся», с другой стороны, заключили настоящий Договор о нижеследующем:</p>

    <?= $this->render('_contract_body', ['contract' => $contract]); ?>

    <table>
        <tr>
            <?= $this->render('_contract_company_contact', ['contract' => $contract]); ?>
            <td style="width: 50%; text-align: left; vertical-align: top;">
                <div style="text-decoration: underline;"><b>Заказчик:</b></div><br>

                Наименование компании-заказчика: <span style="text-decoration: underline; padding: 0 2mm;"><?= $contract->user->parent->name; ?></span><br>

                <span style="display: inline-block; width: 3cm">Юридический адрес: </span><span class="placeholder" style="width: 6cm"></span><br>
                <span style="display: inline-block; width: 0.6cm">тел: </span><span class="placeholder" style="width: 8.4cm"></span><br>
                <span style="display: inline-block; width: 0.8cm">ИНН: </span><span class="placeholder" style="width: 8.2cm"></span><br>
                <span style="display: inline-block; width: 2.2cm">ОКОНХ/ОКЭД: </span><span class="placeholder" style="width: 6.8cm"></span><br>
                <span style="display: inline-block; width: 0.6cm">р/с: </span><span class="placeholder" style="width: 8.4cm"></span><br>
                <span style="display: inline-block; width: 0.8cm">Банк: </span><span class="placeholder" style="width: 8.2cm"></span><br>
                <span style="display: inline-block; width: 1.5cm">Код банка: </span><span class="placeholder" style="width: 7.5cm"></span><br>
                Тел. учащегося: <span class="placeholder" style="width: 3cm;"><?= $contract->user->phoneFull; ?></span><br><br><br>

                <div class="text-right"><span class="placeholder" style="width: 4cm;"></span></div>
                <div class="text-right"><span class="text-center" style="display: inline-block; width: 5cm;">(подпись)</span></div>
                М.П.
            </td>
        </tr>
    </table>
</div>