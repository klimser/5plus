<?php

use common\models\User;
use yii\helpers\Url;
use yii\web\View;

/* @var $this \frontend\components\extended\View */
/* @var $user User|null */
/* @var $users User[] */
/* @var $webpage \common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Url::to(['webpage', 'id' => $webpage->id]), 'label' => 'Онлайн оплата'];
$this->params['breadcrumbs'][] = $user !== null ? $user->name : 'Выбрать студента';

$script = '';
$getStudentButton = function(User $student, bool $label = false) use (&$script) {
    $script .= "Payment.users[{$student->id}] = {
        name: '{$student->nameHidden}',
        age_confirmed: " . ($student->age_confirmed || ($student->parent_id && $student->parent->age_confirmed) ? 'true' : 'false') . ",
        courses: []
    };\n";
    foreach ($student->activeCourseStudents as $courseStudent) {
        $debt = $student->getDebt($courseStudent->course);
        $debt = $debt ? $debt->amount : 0;
        $script .= "Payment.users[{$student->id}].courses.push({
                id: {$courseStudent->course_id},
                name: '{$courseStudent->course->courseConfig->legal_name}',
                priceLesson: {$courseStudent->course->courseConfig->lesson_price},
                priceMonth: {$courseStudent->course->courseConfig->priceMonth},
                priceDiscountLimit: {$courseStudent->course->courseConfig->price12Lesson},
                debt: {$debt},
                paid: '" . ($courseStudent->chargeDateObject ? $courseStudent->chargeDateObject->format('d.m.Y') : '') . "'
            });\n";
    }
    if ($label) {
        return '<h4>' . $student->nameHidden . '</h4>';
    } else {
        return '<button type="button" class="btn btn-lg btn-outline-dark student-button" data-student="' . $student->id . '" onclick="Payment.selectStudent(this);">' . $student->name . '</button>';
    }
};
?>

<div class="container">
    <div class="content-box">
        <div class="row">
            <div id="user_select" class="col-12">
                <?php if ($user !== null): ?>
                    <?= $getStudentButton($user, true); ?>
                <?php
                    $script .= "Payment.user = {$user->id};
                        Payment.renderCourseSelect();\n";
                else:
                    foreach ($users as $user): ?>
                        <?= $getStudentButton($user); ?>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div id="course_select" class="col-12"></div>
        </div>
    </div>
</div>

<?= $this->render('_modal'); ?>

<?php $this->registerJs($script, View::POS_END); ?>
