<?php
/* @var $contract \common\models\Contract */
/* @var $this yii\web\View */
?>

<?= $this->render('_styles'); ?>
<div class="page">
    <?= $this->render('_header', ['contract' => $contract]); ?>

    <p style="text-align: justify;"><?= $contract->company->first_name; ?> (<?= $contract->company->second_name; ?>), осуществляющее образовательную деятельность на основании лицензии <span class="placeholder"><b><?= $contract->company->licence; ?></b></span> в лице директора <span class="placeholder"><b><?= $contract->company->head_name; ?></b></span>, действующего на основании Устава, именуемое в дальнейшем «Учреждение», с одной стороны, и <span class="placeholder"><?= $contract->user->parent->name; ?></span>, в лице директора <span class="placeholder" style="width: 19cm;"></span>  действующего на основании Устава/Положения/Доверенности, именуемый в дальнейшем «Заказчик», с другой стороны, заключили настоящий Договор о нижеследующем:</p>

    <?= $this->render('_body', ['contract' => $contract, 'isIndividual' => false]); ?>

    <table>
        <tr>
            <?= $this->render('_company_contact', ['contract' => $contract]); ?>
            <td style="width: 50%; text-align: left; vertical-align: top;">
                <div class="text-center text-uppercase"><b>Заказчик</b></div>

                <div class="text-center"><b><?= $contract->user->parent->name; ?></b></div>
                
                <span style="display: inline-block; width: 3cm">Адрес: </span><span class="placeholder" style="width: 6cm"></span><br>
                <span class="placeholder" style="width: 9cm"></span><br>
                <span style="display: inline-block; width: 0.8cm">тел: </span><span class="placeholder" style="width: 8.2cm"><?= $contract->user->parent->phoneFull; ?></span><br>
                <span style="display: inline-block; width: 0.8cm">ИНН </span><span class="placeholder" style="width: 3cm"></span>,
                <span style="display: inline-block; width: 1.1cm">ОКЭД </span><span class="placeholder" style="width: 3.3cm"></span><br>
                <span style="display: inline-block; width: 0.8cm">р/с </span><span class="placeholder" style="width: 8.2cm"></span><br>
                <span style="display: inline-block; width: 1cm">в банке </span><span class="placeholder" style="width: 8cm"></span><br>
                <span style="display: inline-block; width: 1.6cm">Код банка: </span><span class="placeholder" style="width: 7.4cm"></span><br><br><br>

                <div class="text-right"><b>Директор <span class="placeholder" style="width: 7.6cm;"></span></b></div>
            </td>
        </tr>
    </table>
</div>
