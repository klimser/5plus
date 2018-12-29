<?php
/* @var $contract \common\models\Contract */
/* @var $this yii\web\View */
?>

<?= $this->render('_styles'); ?>
<div class="page">
    <?= $this->render('_header', ['contract' => $contract]); ?>

    <p style="text-align: justify;">Негосударственное образовательное учреждение «EXCLUSIVE EDUCATION» (Учебный центр «Пять с Плюсом» &trade;), осуществляющее образовательную деятельность на основании лицензии <span class="placeholder"><b>NAS UZ 001.MO.0591-05</b></span> в лице директора <span class="placeholder"><b>Климова Александра Сергеевича</b></span>, действующего на основании Устава, именуемое в дальнейшем «Учреждение», с одной стороны, и <span class="placeholder"><?= $contract->user->parent->name; ?></span> <span class="placeholder" style="width: 19cm;"></span> <span class="placeholder" style="width: 19cm;"></span>, именуемый в дальнейшем «Заказчик» и <span class="placeholder"><?= $contract->user->name; ?></span>, именуемый в дальнейшем «Учащийся», с другой стороны, заключили настоящий Договор о нижеследующем:</p>

    <?= $this->render('_body', ['contract' => $contract]); ?>

    <table>
        <tr>
            <?= $this->render('_company_contact', ['contract' => $contract]); ?>
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