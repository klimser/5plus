<?php

/* @var $this \frontend\components\extended\View */
/* @var $success bool */
/* @var $amount int */
/* @var $group string */
/* @var $discount bool */
/* @var $lessons int */
/* @var $giftCard \common\models\GiftCard */

$this->params['breadcrumbs'][] = ['url' => \yii\helpers\Url::to(['payment/index']), 'label' => 'Онлайн оплата'];
$this->params['breadcrumbs'][] = 'Завершение оплаты';

?>

<div class="row">
    <div class="col-xs-12">
        <h3>Спасибо.</h3>

        <?php if (isset($amount)): ?>
            <div class="alert alert-<?= $success ? 'info' : 'danger'; ?>">
                <table class="table table-condensed">
                    <?php if (!$success): ?>
                        <tr>
                            <td colspan="2"><b>Платёж не был зачислен.</b></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Сумма</th>
                        <td><?= $amount; ?></td>
                    </tr>
                    <?php if (isset($giftCard)): ?>
                        <?= $this->render('gift-card', ['giftCard' => $giftCard]); ?>
                    <?php else: ?>
                        <tr>
                            <th>Группа</th>
                            <td><?= $group; ?></td>
                        </tr>
                        <tr>
                            <th>Оплата со скидкой</th>
                            <td><?= $discount ? 'Да' : 'Нет'; ?></td>
                        </tr>
                        <tr>
                            <th>Занятий</th>
                            <td><?= $lessons; ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>