<?php
/* @var $contract \common\models\Contract */
/* @var $this yii\web\View */
?>

<?= $this->render('_styles'); ?>
<div class="page">
    <?= $this->render('_header', ['contract' => $contract]); ?>

    <p style="text-align: justify;"><?= $contract->company->first_name; ?> (<?= $contract->company->second_name; ?>), осуществляющее образовательную деятельность на основании лицензии <span class="placeholder"><b><?= $contract->company->licence; ?></b></span> в лице директора <span class="placeholder"><b><?= $contract->company->head_name; ?></b></span>, действующего на основании Устава, именуемое в дальнейшем «Учреждение», с одной стороны, и <span class="placeholder"><?= $contract->user->parent_id ? $contract->user->parent->name : $contract->user->name; ?></span>, именуемый в дальнейшем «Заказчик», с другой стороны, заключили настоящий Договор о нижеследующем:</p>

    <?= $this->render('_body', ['contract' => $contract, 'isIndividual' => true]); ?>

    <table>
        <tr>
            <?= $this->render('_company_contact', ['contract' => $contract]); ?>
            <td style="width: 50%; text-align: left; vertical-align: top;">
                <div class="text-center text-uppercase"><b>Заказчик</b></div>

                <div class="text-center"><b><?= $contract->user->parent_id ? $contract->user->parent->name : $contract->user->name; ?></b></div>

                <span style="display: inline-block; width: 1.6cm">Адрес: </span><span class="placeholder" style="width: 7.4cm"></span><br>
                <span class="placeholder" style="width: 9cm"></span><br>

                Паспорт: серия <span class="placeholder" style="width: 1cm"></span> № <span class="placeholder" style="width: 2cm"></span><br>
                выдан «<span class="placeholder" style="width: 6mm"></span>»
                «<span class="placeholder" style="width: 6mm"></span>»
                «<span class="placeholder" style="width: 12mm"></span>» г.<br>

                <span class="placeholder" style="width: 9cm"></span><br>

                Тел.: <span class="placeholder" style="width: 3cm;"><?= $contract->user->parent_id ? $contract->user->parent->phoneFull : $contract->user->phoneFull; ?></span>
                <span class="placeholder" style="width: 3cm;"><?= $contract->user->parent_id ? $contract->user->parent->phone2Full : $contract->user->phone2Full; ?></span><br>
                Тел. учащегося: <span class="placeholder" style="width: 3cm;"><?= $contract->user->phoneFull; ?></span><br><br><br>

                <div class="text-right"><b> <span class="placeholder" style="width: 4cm;"></span> <?= $contract->user->parent_id ? $contract->user->parent->name : $contract->user->name; ?></b></div>
            </td>
        </tr>
    </table>
</div>
