<?php

use yii\bootstrap\Html;

/* @var $this \frontend\components\extended\View */
/* @var $user \common\models\User|null */
/* @var $users \common\models\User[] */

$this->params['breadcrumbs'][] = ['url' => \yii\helpers\Url::to(['payment/index']), 'label' => 'Онлайн оплата'];
$this->params['breadcrumbs'][] = $user !== null ? $user->name : 'Выбрать студента';

$script = '';
$getPupilButton = function(\common\models\User $pupil, bool $label = false) use (&$script) {
    $script .= "Payment.users[{$pupil->id}] = {
        name: '{$pupil->name}',
        groups: []
    };\n";
    foreach ($pupil->activeGroupPupils as $groupPupil) {
        $monthPrice = $groupPupil->group->lesson_price * \common\components\GroupComponent::getTotalClasses($groupPupil->group->weekday);
        $debt = $pupil->getDebt($groupPupil->group);
        $debt = $debt ? $debt->amount : 0;
        $script .= "Payment.users[{$pupil->id}].groups.push({
                id: {$groupPupil->group_id},
                name: '{$groupPupil->group->name}',
                price: {$monthPrice},
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

<div class="row">
    <div id="user_select" class="col-xs-12">
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
    <div id="group_select" class="col-xs-12">

    </div>
</div>
</div>

<div id="payment_form" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= Html::beginForm(\yii\helpers\Url::to(['payment/create']), 'post', ['onsubmit' => 'return Payment.completePayment(this);']); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title">Оплатить</h4>
            </div>
            <div class="modal-body">
                <div id="message_board"></div>
                <div id="order_form_body" class="form-horizontal">
                    <div class="form-group">
                        <label for="pupil" class="col-xs-4 col-sm-3 col-lg-2 control-label">Студент</label>
                        <div class="col-xs-8 col-sm-9 col-lg-10">
                            <input class="form-control" id="pupil" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="group" class="col-xs-4 col-sm-3 col-lg-2 control-label">Группа</label>
                        <div class="col-xs-8 col-sm-9 col-lg-10">
                            <input class="form-control" id="group" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="amount" class="col-xs-4 col-sm-3 col-lg-2 control-label">Сумма</label>
                        <div class="col-xs-8 col-sm-9 col-lg-10">
                            <input class="form-control" id="amount" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12 text-center">
                            <button class="btn btn-primary" id="pay_button">Подтвердить и перейти к оплате</button>
                        </div>
                    </div>
                </div>
            </div>
            <?= Html::endForm(); ?>
        </div>
    </div>
</div>
<div class="container"></div>

<?php $this->registerJs($script, \yii\web\View::POS_END); ?>