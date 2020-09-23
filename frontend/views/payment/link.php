<?php

use yii\helpers\Url;
use yii\web\View;

/* @var $this \frontend\components\extended\View */
/* @var $paymentLink \common\models\PaymentLink|null */
/* @var $groupPupils \common\models\GroupPupil[] */

$this->params['breadcrumbs'][] = ['url' => Url::to(['payment/index']), 'label' => 'Онлайн оплата'];
$this->params['breadcrumbs'][] = $paymentLink !== null ? $paymentLink->user->nameHidden : 'Не найден';

if ($paymentLink) {
    $script ="
        Payment.users[{$paymentLink->user_id}] = {
            name: '{$paymentLink->user->nameHidden}',
            groups: []
        };
        Payment.user = {$paymentLink->user_id};
    ";
    $this->registerJs($script, View::POS_END);
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
                    <?= $paymentLink->user->nameHidden; ?> | <?= $paymentLink->group->legal_name; ?> <?= $debt ? '' : '<small>оплачено до ' . ($payDate ? $payDate->format('d.m.Y') : '') . '</small>'; ?>
                </h4>
                <div class="row">
                    <?php if ($debt > 0): ?>
                        <div class="col-12 col-md-auto mb-2">
                            <button class="btn btn-primary btn-block" data-sum="<?= $debt; ?>" onclick="Payment.selectSum(this);">
                                Погасить задолженность <?= $debt; ?> сум
                            </button>
                        </div>
                    <?php endif; ?>
            
                    <div class="col-12 col-md-auto mb-2">
                        <button class="btn btn-secondary btn-block" data-sum="<?= $paymentLink->group->priceMonth; ?>" onclick="Payment.selectSum(this);">
                            за 1 месяц <?= $paymentLink->group->priceMonth; ?> сум
                        </button>
                    </div>
            
                    <div class="col-12 col-md-auto mb-2">
                        <button class="btn btn-secondary btn-block" data-sum="<?= $paymentLink->group->price4Month; ?>" onclick="Payment.selectSum(this);">
                            за 4 месяца <?= $paymentLink->group->price4Month; ?> сум
                        </button>
                    </div>

                    <div class="col-12 col-md-auto mb-2">
                        <button class="btn btn-secondary btn-block" data-sum="none" onclick="Payment.selectSum(this);">другая сумма</button>
                    </div>
                </div>
            </div>

            <?= $this->render('_modal'); ?>
        <?php endif; ?>
    </div>
</div>
