<?php

use common\models\User;
use yii\bootstrap4\Html;
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
        name: '{$pupil->name}',
        groups: []
    };\n";
    foreach ($pupil->activeGroupPupils as $groupPupil) {
        $debt = $pupil->getDebt($groupPupil->group);
        $debt = $debt ? $debt->amount : 0;
        $script .= "Payment.users[{$pupil->id}].groups.push({
                id: {$groupPupil->group_id},
                name: '{$groupPupil->group->legal_name}',
                price: {$groupPupil->group->priceMonth},
                priceDiscount: {$groupPupil->group->price3Month},
                debt: {$debt},
                paid: '" . ($groupPupil->chargeDateObject ? $groupPupil->chargeDateObject->format('d.m.Y') : '') . "'
            });\n";
    }
    if ($label) {
        return '<h4>' . $pupil->name . '</h4>';
    } else {
        return '<button type="button" class="btn btn-lg btn-default pupil-button" data-pupil="' . $pupil->id . '" onclick="Payment.selectPupil(this);">' . $pupil->name . '</button>';
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

<div id="payment_form" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= Html::beginForm(Url::to(['payment/create']), 'post', ['onsubmit' => 'return Payment.completePayment(this);']); ?>
            <div class="modal-header">
                <h4 class="modal-title">Оплатить</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="message_board"></div>
                <div id="order_form_body" class="form-horizontal">
                    <div class="form-group">
                        <label for="pupil" class="col-4 col-md-3 col-xl-2 control-label">Студент</label>
                        <div class="col-8 col-md-9 col-xl-10">
                            <input class="form-control" id="pupil" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="group" class="col-4 col-md-3 col-xl-2 control-label">Группа</label>
                        <div class="col-8 col-md-9 col-xl-10">
                            <input class="form-control" id="group" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="amount" class="col-4 col-md-3 col-xl-2 control-label">Сумма</label>
                        <div class="col-8 col-md-9 col-xl-10">
                            <input class="form-control" id="amount" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-12 text-center">
                            <button class="btn btn-primary pay_button">Подтвердить и перейти к оплате</button>
                        </div>
                    </div>
                </div>
            </div>
            <?= Html::endForm(); ?>
        </div>
    </div>
</div>

<?php $this->registerJs($script, View::POS_END); ?>
