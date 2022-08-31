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
$getPupilButton = function(User $pupil, bool $label = false) use (&$script) {
    $script .= "Payment.users[{$pupil->id}] = {
        name: '{$pupil->nameHidden}',
        age_confirmed: " . ($pupil->age_confirmed || ($pupil->parent_id && $pupil->parent->age_confirmed) ? 'true' : 'false') . ",
        groups: []
    };\n";
    foreach ($pupil->activeCourseStudents as $groupPupil) {
        $debt = $pupil->getDebt($groupPupil->group);
        $debt = $debt ? $debt->amount : 0;
        $script .= "Payment.users[{$pupil->id}].groups.push({
                id: {$groupPupil->group_id},
                name: '{$groupPupil->group->legal_name}',
                priceLesson: {$groupPupil->group->lesson_price},
                priceMonth: {$groupPupil->group->priceMonth},
                priceDiscountLimit: {$groupPupil->group->price12Lesson},
                debt: {$debt},
                paid: '" . ($groupPupil->chargeDateObject ? $groupPupil->chargeDateObject->format('d.m.Y') : '') . "'
            });\n";
    }
    if ($label) {
        return '<h4>' . $pupil->nameHidden . '</h4>';
    } else {
        return '<button type="button" class="btn btn-lg btn-outline-dark pupil-button" data-pupil="' . $pupil->id . '" onclick="Payment.selectPupil(this);">' . $pupil->name . '</button>';
    }
};
?>

<div class="container">
    <div class="content-box">
        <div class="row">
            <div id="user_select" class="col-12">
                <?php if ($user !== null): ?>
                    <?= $getPupilButton($user, true); ?>
                <?php
                    $script .= "Payment.user = {$user->id};
                        Payment.renderGroupSelect();\n";
                else:
                    foreach ($users as $user): ?>
                        <?= $getPupilButton($user); ?>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div id="group_select" class="col-12"></div>
        </div>
    </div>
</div>

<?= $this->render('_modal'); ?>

<?php $this->registerJs($script, View::POS_END); ?>
