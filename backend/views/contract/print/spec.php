<?php
/* @var $contract \common\models\Contract */
/* @var $this yii\web\View */

$perWeek = $contract->groupParam->classesPerWeek;

use yii\helpers\Url; ?>

<?= $this->render('_styles'); ?>
<style>
    body {
        font-size: 3.9mm;
    }
</style>

<div style="margin: 0.5cm;">
    <img src='data:image/png;base64,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' alt="Logo" />
    <div style="float: right; width: 3cm; height: 3cm; margin: 0 0 5mm 5mm;">
        <img src="<?= Url::to(['contract/qr']); ?>" style="width: 3cm;" alt="https://t.me/fiveplus_public_bot?start=account">
    </div>

    <h1 class="text-center">
        Спецификация <span style="font-size: 0.6em;"><?= $contract->number; ?></span>
    </h1>

    <table>
        <tr>
            <td class="text-left">г. Ташкент</td>
            <td class="text-right"><?= $contract->createDate->format('d.m.Y'); ?></td>
        </tr>
    </table>
    <table class="bordered">
        <tr>
            <th>Наименование предмета</th>
            <th>Сумма оплаты (сум)</th>
            <th>Длительность (уч мес*)</th>
            <th>Продолжительность 1 занятия (мин)</th>
            <th>Описание предмета</th>
        </tr>
        <tr>
            <td><?= $contract->group->subject->name; ?></td>
            <td class="text-center"><?= number_format($contract->amount, 0, '.', ' '); ?></td>
            <td class="text-center"><?= number_format($contract->monthCount, 2, '.', ''); ?></td>
            <td class="text-center"><?= $contract->group->lesson_duration; ?></td>
            <td>
                Периодичность проведения занятий: <?= $perWeek; ?> раз<?= $perWeek > 1 ? 'а' : ''; ?> в неделю. Занятия проводятся с понедельника по субботу согласно утвержденному учебному расписанию.
            </td>
        </tr>
    </table>
    <p style="color: gray; font-size: smaller;">
        * 1 учебный месяц = 28 календарных дней<br>
        ** При запросе возврата внесенных за обучение остатков денежных средств по любой причине, не зависящей от ООО "Exclusive Education", последняя внесенная за обучение оплата пересчитывается по стоимости за 1 занятие с повышающим коэффициентом 1,2 к текущей стоимости 1 занятия при условии, что было проведено менее 12 занятий.<br>
        *** При увеличении или уменьшении Исполнителем стоимости занятий, новая установленная стоимость занятий применяется с даты официального утверждения и объявления на сайте www.5plus.uz, а все внесенные до момента изменения стоимости занятий денежные средства перерасчитываются согласно новой установленной стоимости занятий.
    </p>
    <br>
    <p>Со спецификацией ознакомлен и согласен, Оплату подтверждаю</p>
    <p>Студент или его законный представитель: <b><?= $contract->user->name; ?></b></p>
    <br>
    <p><b>Юридический и почтовый адрес и банковские реквизиты:</b></p>
    <p><i>Юридический адрес: 100140, г. Ташкент, микрорайон ТашГрэс, д. 20, кв. 52.<br>
    Адрес нахождения курсов: г. Ташкент, Мирабадский район, ул. Ойбек, 16<br>
    Тел.: +99871 200-03-50<br>
    р/с 20208000400444136001<br>
    в ОПЕРУ АИТБ «Ипак Йули».<br>
    МФО 00444.<br>
    ИНН 303237699<br>
    ОКЭД 85.59.0.<br>
    E-mail: 5plus.center@gmail.com</i></p>
    <p></p>
    <p>
        Директор ООО «EXCLUSIVE EDUCATION», Климов Александр Сергеевич <img src="<?= require_once "sign.php"; ?>" style="width: 6cm;" alt="signature">
    </p>
    <p></p>
    <p></p>
    <p>
        <img src="<?= require_once "stamp.php"; ?>" style="width: 4cm;" alt="stamp">
    </p>
</div>
