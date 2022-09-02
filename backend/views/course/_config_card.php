<?php
/* @var $this \yii\web\View */
/* @var $form \yii\bootstrap4\ActiveForm */
/* @var $courseConfig \common\models\CourseConfig */
/* @var $teacherList array<\common\models\Teacher> */

use common\components\helpers\Calendar;

?>
<table class="table table-sm table-bordered">
    <tr>
        <th>Название</th>
        <td><?= $courseConfig->name; ?></td>
    </tr>
    <tr>
        <th>Официальное название</th>
        <td><?= $courseConfig->legal_name; ?></td>
    </tr>
    <tr>
        <th>Преподаватель</th>
        <td><?= $courseConfig->teacher->name; ?></td>
    </tr>
    <tr>
        <th>ЗП преподавателя</th>
        <td>
            <?php if ($courseConfig->teacher_lesson_pay > 0): ?>
                <?= $courseConfig->teacher_lesson_pay; ?> за занятие
            <?php else: ?>
                <?= $courseConfig->teacher_rate; ?> %
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th>Кабинет</th>
        <td><?= $courseConfig->room_number; ?></td>
    </tr>
    <tr>
        <th>Цена зантия</th>
        <td><?= $courseConfig->lesson_price; ?></td>
    </tr>
    <tr>
        <th>Цена занятия со скидкой</th>
        <td><?= $courseConfig->lesson_price_discount; ?></td>
    </tr>
    <tr>
        <th>Продолжительность занятия</th>
        <td><?= $courseConfig->lesson_duration; ?> мин</td>
    </tr>
    <tr>
        <th>Расписание</th>
        <td>
            <?php foreach ($courseConfig->schedule as $i => $time):
                if (!empty($time)): ?>
                    <?= Calendar::$weekDaysShort[($i + 1) % 7]; ?> <b><?= $time; ?></b><br>
                <?php endif;
            endforeach; ?>
        </td>
    </tr>
</table>
