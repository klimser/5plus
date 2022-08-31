<?php
/* @var $this \yii\web\View */
/* @var $form \yii\bootstrap4\ActiveForm */
/* @var $groupConfig \common\models\CourseConfig */
/* @var $teacherList array<\common\models\Teacher> */

use common\components\helpers\Calendar;

?>
<table class="table table-sm table-bordered">
    <tr>
        <th>Период</th>
        <td><?= $groupConfig->date_from; ?> - <?= $groupConfig->date_to; ?></td>
    </tr>
    <tr>
        <th>Преподаватель</th>
        <td><?= $groupConfig->teacher->name; ?></td>
    </tr>
    <tr>
        <th>ЗП преподавателя</th>
        <td><?= $groupConfig->teacher_rate; ?> %</td>
    </tr>
    <tr>
        <th>Кабинет</th>
        <td><?= $groupConfig->room_number; ?></td>
    </tr>
    <tr>
        <th>Цена зантия</th>
        <td><?= $groupConfig->lesson_price; ?></td>
    </tr>
    <tr>
        <th>Цена занятия со скидкой</th>
        <td><?= $groupConfig->lesson_price_discount; ?></td>
    </tr>
    <tr>
        <th>Продолжительность занятия</th>
        <td><?= $groupConfig->lesson_duration; ?> мин</td>
    </tr>
    <tr>
        <th>Расписание</th>
        <td>
            <?php foreach ($groupConfig->schedule as $i => $time):
                if (!empty($time)): ?>
                    <?= Calendar::$weekDaysShort[($i + 1) % 7]; ?> <b><?= $time; ?></b><br>
                <?php endif;
            endforeach; ?>
        </td>
    </tr>
</table>
