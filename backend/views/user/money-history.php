<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $payments \common\models\Payment[] */
/* @var $user \common\models\User */
/* @var $pager \yii\data\Pagination */

$this->title = 'История платежей';
if (Yii::$app->user->identity->role == \common\models\User::ROLE_ROOT) {
    $this->params['breadcrumbs'][] = ['label' => 'Студенты', 'url' => ['money-history']];
    $this->params['breadcrumbs'][] = $user->name;
} elseif (Yii::$app->user->identity->role == \common\models\User::ROLE_PARENTS && count(Yii::$app->user->identity->children) > 1) {
    $this->params['breadcrumbs'][] = ['label' => 'Мои дети', 'url' => ['money-history']];
    $this->params['breadcrumbs'][] = $this->title . ': ' . $user->name;
} else {
    $this->params['breadcrumbs'][] = $this->title;
}

?>
<div class="money-history">
    <div class="row">
        <div class="col-xs-12">
            <h1 class="pull-left no-margin-top"><?= Html::encode($this->title) ?></h1>
            <?= \backend\components\DebtWidget::widget(['user' => Yii::$app->user->identity]); ?>
        </div>
        <div class="clearfix"></div>
        <?php /*
        <?= \backend\components\FuturePaymentsWidget::widget(['user' => Yii::$app->user->identity]); ?>
        */ ?>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <table class="table table-bordered table-striped">
                <tr>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Комментарий</th>
                </tr>
                <?php foreach ($payments as $payment): ?>
                    <tr class="<?= $payment->amount > 0 ? 'success' : 'danger'; ?>">
                        <td><?= $payment->createDate->format('d.m.Y'); ?></td>
                        <td><?= $payment->amount; ?></td>
                        <td><?= $payment->amount < 0 ? $payment->comment : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?= \yii\widgets\LinkPager::widget(['pagination' => $pager]); ?>
</div>
