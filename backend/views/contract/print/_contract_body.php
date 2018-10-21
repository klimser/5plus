<?php
/* @var $contract \backend\models\Contract */
/* @var $this yii\web\View */
?>

    <div class="text-center"><b>1. Общие положения</b></div>
    <table>
        <tr>
            <td class="number">1.1.</td>
            <td>
                Договор регулирует отношения между Учреждением и Заказчиком, складывающиеся по поводу обучения лица, именуемого в дальнейшем «Учащийся», и имеет целью определение взаимных прав, обязанностей и ответственности Учреждения и Заказчика в пери-од действия настоящего Договора.
            </td>
        </tr>
        <tr>
            <td class="number">1.2.</td>
            <td>
                В случае если Заказчик и Учащийся представлены в одном лице, обязанности Заказчика по Договору исполняет Учащийся.
            </td>
        </tr>
        <tr>
            <td class="number">1.3.</td>
            <td>
                Взаимоотношения сторон, не оговоренные настоящим Договором, регулируются нормами действующего законодательства Республики Узбекистан.
            </td>
        </tr>
    </table>

    <div class="text-center"><b>2. Предмет Договора</b></div>
    <table>
        <tr>
            <td class="number">2.1.</td>
            <td>
                Учащийся (физическое лицо, направляемое Заказчиком) принимается в Учреждение на платной основе для обучения по программе дополнительного профессионального образования «<?= $contract->group->type->name; ?>»  очной формы обучения, именуемой в дальнейшем «Программа».
            </td>
        </tr>
        <tr>
            <td class="number">2.2.</td>
            <td>
                Нормативный срок освоения Программы составляет <span class="placeholder" style="width: 1cm;"></span> академических часов (суммарно по всем предметам обучения). Один академический час равен 40 минут.
            </td>
        </tr>
        <tr>
            <td class="number">2.3.</td>
            <td>
                Нормативная продолжительность обучения составляет <span class="placeholder" style="width: 1cm;"></span> недель.
            </td>
        </tr>
        <tr>
            <td class="number">2.4.</td>
            <td>
                Настоящий договор заключается на срок нормативной продолжительности обучения.
            </td>
        </tr>
    </table>

    <div class="text-center"><b>3. Обязанности Учреждения</b></div>
    <table>
        <tr>
            <td class="number">3.1.</td>
            <td>
                Зачислить Учащегося для обучения по Программе по результатам вступительного тестирования, на основании настоящего Договора и представленных документов об оплате обучения.
            </td>
        </tr>
        <tr>
            <td class="number">3.2.</td>
            <td>
                Выдать Учащемуся, успешно прошедшему полный курс обучения и итоговую аттестацию, Сертификат о прохождении обучения и окончании обучения в Учреждении.
            </td>
        </tr>
        <tr>
            <td class="number">3.3.</td>
            <td>
                Предоставить Учащемуся возможность обучения по Программе в соответствии с учебным планом и графиком учебного процесса (обеспечение Учащегося доступом к сети Интернет и техническими средствами для использования возможностей дополнительного учебно-методического комплекса производится Учащимся самостоятельно).
            </td>
        </tr>
        <tr>
            <td class="number">3.4.</td>
            <td>
                Обеспечить Учащегося учебными материалами в рамках курса (один учебник).
            </td>
        </tr>
        <tr>
            <td class="number">3.5.</td>
            <td>
                Сохранить место за Учащимся в случае пропуска занятий по уважительным причинам (с учетом своевременной оплаты оказываемых услуг). Уважительными являются причины:
            </td>
        </tr>
        <tr>
            <td class="number">3.5.1.</td>
            <td>
                Болезнь более 2-х недель при наличии подтверждающего документа (справки из поликлиники либо больничного листа)
            </td>
        </tr>
        <tr>
            <td class="number">3.5.2.</td>
            <td>
                Отпуск или отъезд за границу на срок более 2-х недель
            </td>
        </tr>
        <tr>
            <td class="number">3.6.</td>
            <td>
                Обеспечить проведение учебных занятий, предусмотренных учебным планом.
            </td>
        </tr>
    </table>

    <div class="text-center"><b>4. Обязанности Заказчика</b></div>
    <table>
        <tr>
            <td class="number">4.1.</td>
            <td>
                Своевременно в соответствии с п.6. Договора производить оплату обучения.
            </td>
        </tr>
        <tr>
            <td class="number">4.2.</td>
            <td>
                Обеспечить посещение Учащимся занятий согласно учебному расписанию.
            </td>
        </tr>
        <tr>
            <td class="number">4.3.</td>
            <td>
                Извещать Учреждение об уважительных причинах отсутствия Учащегося.
            </td>
        </tr>
        <tr>
            <td class="number">4.4.</td>
            <td>
                Самостоятельно ознакомить Учащегося с его обязанностями по данному Договору.
            </td>
        </tr>
        <tr>
            <td class="number">4.5.</td>
            <td>
                Возмещать ущерб, причиненный Учащимся имуществу Учреждения, в соответствии с законодательством Республики Узбекистан.
            </td>
        </tr>
    </table>

    <div class="text-center"><b>5. Обязанности Учащегося</b></div>
    <table>
        <tr>
            <td class="number">5.1.</td>
            <td>
                Своевременно выполнять учебный план Программы, посещать занятия, указанные в учебном расписании.
            </td>
        </tr>
        <tr>
            <td class="number">5.2.</td>
            <td>
                Выполнять задания по подготовке к занятиям по Программе обучения.
            </td>
        </tr>
        <tr>
            <td class="number">5.3.</td>
            <td>
                Не передавать третьим лицам полномочий по доступу к учебно-методическому комплексу и учебному процессу Учреждения.
            </td>
        </tr>
        <tr>
            <td class="number">5.4.</td>
            <td>
                Использовать учебно-методический комплекс только для личного изучения и не использовать полученный от Учреждения учебно-методический комплекс в иных целях.
            </td>
        </tr>
        <tr>
            <td class="number">5.5.</td>
            <td>
                Соблюдать требования внутреннего распорядка Учреждения и иных локальных нормативных актов, соблюдать учебную дисциплину и общепринятые нормы поведения.
            </td>
        </tr>
        <tr>
            <td class="number">5.6.</td>
            <td>
                В случае невозможности явки на занятие по любой причине предупреждать об этом не менее чем за 6 (шесть) часов до указанного в учебном расписании времени.
            </td>
        </tr>
        <tr>
            <td class="number">5.7.</td>
            <td>
                В случае несвоевременной сдачи аттестационных работ по вине Учащегося, заключить с Учреждением дополнительное соглашение на пересдачу Учащимся указанных аттестационных работ с отдельной оплатой согласно Прейскуранту на образовательные услуги.
            </td>
        </tr>
    </table>

    <div class="text-center"><b>6. Оплата за обучение и порядок расчетов</b></div>
    <table>
        <tr>
            <td class="number">6.1.</td>
            <td>
                Стоимость образовательных услуг в рамках настоящего Договора составляет <span class="placeholder"><?= $contract->amount; ?></span> сум (<span class="placeholder"><?= $contract->amountString; ?></span> сум)
            </td>
        </tr>
        <tr>
            <td class="number">6.2.</td>
            <td>
                В рамках настоящего договора Учащийся посещает следующие курсы:
                <ul><li><?= $contract->group->legal_name; ?></li></ul>
            </td>
        </tr>
        <tr>
            <td class="number">6.3.</td>
            <td>
                Оплата образовательных услуг производится путем предоплаты за весь курс согласно длительности, указанной в п.2.3 настоящего договора. Порядок оплаты услуг и стоимость обучения, предусмотренные настоящим договором, а также изменение ежемесячной оплаты согласно скидкам, могут быть пересмотрены Учреждением в одностороннем порядке, о чем составляется письменное дополнительное соглашение к настоящему Договору между Учреждением и Заказчиком.
            </td>
        </tr>
        <tr>
            <td class="number">6.4.</td>
            <td>
                Заказчик вправе прекратить посещение одного или нескольких курсов обучения (при обучении более чем по 1 предмету) с пересмотром стоимости обучения и удержанием всех ранее уплаченных за обучение сумм с обязательным составлением письменного допол-нительного соглашения к настоящему договору об изменении списка посещаемых курсов и стоимости.
            </td>
        </tr>
    </table>
</div>
<div class="page">
    <table>
        <tr>
            <td class="number">6.5.</td>
            <td>
                Оплата производится по безналичному расчету на расчетный счет Учреждения, как до даты заключения данного Договора, так и после даты заключения данного Договора (п.п.6.2, 6.3.), либо посредством пластиковых карт UZCARD, либо наличными, либо с помощью интернет-платежей (Payme).
            </td>
        </tr>
        <tr>
            <td class="number">6.6.</td>
            <td>
                Датой оплаты образовательных услуг по настоящему Договору признается дата зачисления денежных средств на расчетный счет Учреждения.
            </td>
        </tr>
    </table>

    <div class="text-center"><b>7. Сроки действия Договора и условия его дополнения и расторжения</b></div>
    <table>
        <tr>
            <td class="number">7.1.</td>
            <td>
                Настоящий Договор вступает в силу с момента оплаты Заказчиком данной образовательной Программы и действует до окончания образовательной Программы. Срок действия Договора не превышает нормативный срок обучения (п.п.2.3.).
            </td>
        </tr>
        <tr>
            <td class="number">7.2.</td>
            <td>
                Занятия, пропущенные Учащимся по любой причине, в т.ч. по причине болезни до 2-х недель, занятости и иным причинам, не восстанавливаются и не подлежат перерасчету в счет будущих занятий (кроме указанных в пункте 3.5 настоящего договора)
            </td>
        </tr>
        <tr>
            <td class="number">7.3.</td>
            <td>
                Занятия, отмененные Учреждением по любой причине, в т.ч. по причине болезни преподавателя и иным причинам, напрямую зависящим от Учреждения, переносятся на другой день, либо подлежат перерасчету в счет будущих занятий.
            </td>
        </tr>
        <tr>
            <td class="number">7.4.</td>
            <td>
                Занятия, отмененные Учреждением по причине официально установленных государственных праздников и установленных государством официальных выходных дней, закрытии здания и прочих не зависящих от Учреждения, но зависящих от государства, причин не восстанавливаются и не пересчитываются в счет будущих занятий.
            </td>
        </tr>
        <tr>
            <td class="number">7.5.</td>
            <td>
                Занятия, попавшие на праздничные дни (1 января, 8 марта,21 марта,9 мая, Уруза-хайит , курбан-хайит,  1 сентября, 1 октября, 8 декабря ) являются официальными выходными, не перерасчитываются и не восстанавливаются.
            </td>
        </tr>
        <tr>
            <td class="number">7.6.</td>
            <td>
                Оплата,  внесенная за обучение,  не возвращается по любым причинам, не зависящим от НОУ «Exclusive Education».
            </td>
        </tr>
        <tr>
            <td class="number">7.7.</td>
            <td>
                Получение Заказчиком у Учреждения документа о прохождении обучения (Сертификат) является формой соглашения сторон об окончании образовательной Программы (актом выполненных работ) и отсутствия взаимных претензий сторон друг к другу. При невыдаче Сертификата со стороны Учреждения между Заказчиком и Учреждением составляется акт выполненных работ с указанием уплаченной за обучение суммы. Подпись Заказчиком акта выполненных работ подтверждает отсутствие претензий со стороны Заказчика к Учреждению.
            </td>
        </tr>
        <tr>
            <td class="number">7.8.</td>
            <td>
                В случае неисполнения или ненадлежащего исполнения сторонами обязательств по настоящему Договору они несут ответственность, предусмотренную Гражданским кодексом Республики Узбекистан, Законом Республики Узбекистан «О защите прав потребителей» и иными нормативными правовыми актами.
            </td>
        </tr>
        <tr>
            <td class="number">7.9.</td>
            <td>
                Невозможность исполнения Договора, возникшая по вине Заказчика или Учащегося (неоплата обучения в размере и в сроках, предусмотренных Договором, отказ от оплаты), невыполнение учебного плана в установленные сроки, нарушение правил внутреннего распорядка, невыполнение в установленные сроки учебных заданий, прекращение обучения Учащимся без уважительной причины – являются основанием для отчисления Учащегося и расторжения данного Договора с удержанием Учреждением всех ранее внесенных сумм за обучение.
            </td>
        </tr>
        <tr>
            <td class="number">7.10.</td>
            <td>
                Настоящий Договор может быть расторгнут досрочно:
                <ul>
                    <li>по взаимному соглашению сторон.</li>
                    <li>по заявлению Заказчика, при условии оплаты Учреждению фактически понесенных расходов по данному Договору.</li>
                    <li>Учреждением в одностороннем порядке по причине прекращения ежемесячной оплаты со стороны Заказчика с составлением акта о расторжении настоящего договора.</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="number">7.11.</td>
            <td>
                Учащийся вправе расторгнуть настоящий Договор только с письменного согласия Заказчика.
            </td>
        </tr>
    </table>

    <div class="text-center"><b>8. Заключительные положения</b></div>
    <table>
        <tr>
            <td class="number">8.1.</td>
            <td>
                Изменения и дополнения в настоящий Договор вносятся по согласию сторон и оформляются в виде дополнительных соглашений к настоящему Договору.
            </td>
        </tr>
        <tr>
            <td class="number">8.2.</td>
            <td>
                Учреждение вправе использовать в рекламных целях информацию о результатах обучения Учащегося (поступления в ВУЗ, лицей, колледж, сдача любых экзаменов и копии сертификатов) без предварительного уведомления Заказчика.
            </td>
        </tr>
        <tr>
            <td class="number">8.3.</td>
            <td>
                Условия Договора могут быть изменены в результате форс-мажорных обстоятельств (стихийные бедствия и др.), а также при вступлении данного Договора в противоречие с вновь принятыми нормативными актами Республики Узбекистан.
            </td>
        </tr>
        <tr>
            <td class="number">8.4.</td>
            <td>
                Все споры, возникающие при исполнении и расторжении настоящего Договора, разрешаются путем непосредственных переговоров, а при не достижении согласия – в судебном порядке. При нахождении одной из сторон за пределами Республики Узбекистан, спор рассматривается на территории Республики Узбекистан.
            </td>
        </tr>
        <tr>
            <td class="number">8.5.</td>
            <td>
                Договор составлен в двух экземплярах, один – для Заказчика, другой – для Учреждения. Каждый экземпляр имеет равную юридическую силу.
            </td>
        </tr>
    </table>

    <div class="text-center"><b>9. Реквизиты и подписи сторон</b></div>