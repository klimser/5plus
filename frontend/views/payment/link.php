<?php

use yii\bootstrap4\Html;

/* @var $this \frontend\components\extended\View */
/* @var $paymentLink \common\models\PaymentLink|null */
/* @var $groupPupils \common\models\GroupPupil[] */

$this->params['breadcrumbs'][] = ['url' => \yii\helpers\Url::to(['payment/index']), 'label' => 'Онлайн оплата'];
$this->params['breadcrumbs'][] = $paymentLink !== null ? $paymentLink->user->nameHidden : 'Не найден';

if ($paymentLink) {
    $script ="
        Payment.users[{$paymentLink->user_id}] = {
            name: '{$paymentLink->user->nameHidden}',
            groups: []
        };
        Payment.user = {$paymentLink->user_id};
    ";
    $this->registerJs($script, \yii\web\View::POS_END);
}
?>

<div class="container">
    <div class="content-box payment-panel">
        <?php if (!$paymentLink): ?>
            <h3>Неверная ссылка!</h3>
        <?php else: ?>
            <div id="payment-<?= $paymentLink->group_id; ?>" class="group-payments" data-groupid="<?= $paymentLink->group_id; ?>" data-groupname="<?= $paymentLink->group->legal_name; ?>">
                <br>
                <?php
                    $debt = $paymentLink->user->getDebt($paymentLink->group);
                    $debt = $debt ? $debt->amount : 0;
                    $payDate = null;
                    if (!$debt) {
                        foreach ($groupPupils as $groupPupil) {
                            if ($groupPupil->chargeDateObject && ($payDate === null || $payDate > $groupPupil->chargeDateObject)) {
                                $payDate = $groupPupil->chargeDateObject;
                            }
                        }
                    }
                ?>
                <h4>
                    <?= $paymentLink->user->nameHidden; ?> | <?= $paymentLink->group->legal_name; ?> <?= $debt ? '' : '<small>оплачено до ' . ($payDate ? $payDate->format('d.m.Y') :'') . '</small>'; ?>
                </h4>
                <div class="row">
                    <?php if ($debt > 0): ?>
                        <div class="col-md-6 col-lg-4">
                            <button class="btn btn-primary full-width" data-sum="<?= $debt; ?>" onclick="Payment.selectSum(this);">
                                Погасить задолженность <?= $debt; ?> сум
                            </button>
                        </div>
                    <?php endif; ?>
            
                    <div class="col-md-6 col-lg-4">
                        <button class="btn btn-default full-width" data-sum="<?= $paymentLink->group->priceMonth; ?>" onclick="Payment.selectSum(this);">
                            за 1 месяц <?= $paymentLink->group->priceMonth; ?> сум
                        </button>
                    </div>
            
                    <div class="col-md-6 col-lg-4">
                        <button class="btn btn-default full-width" data-sum="<?= $paymentLink->group->price3Month; ?>" onclick="Payment.selectSum(this);">
                            за 3 месяца <?= $paymentLink->group->price3Month; ?> сум
                        </button>
                    </div>
            
                    <div class="col-md-6 col-lg-4">
                        <div class="input-group">
                            <input type="number" min="1000" step="1000" class="form-control custom_sum" placeholder="сумма">
                            <span class="input-group-btn">
                                <button class="btn btn-default" data-sum="none" onclick="Payment.selectSum(this);">другая сумма</button>
                            </span>
                        </div>
                    </div>
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
            <div class="container">
        <?php endif; ?>
    </div>
</div>
