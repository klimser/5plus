<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $pupilCollection \common\models\User[] */
/* @var $user \common\models\User */

$this->title = 'Мои дети';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="user_list">
    <div class="row">
        <div class="col-xs-12">
            <h1 class="float-left mt-0"><?= Html::encode($this->title) ?></h1>
            <?= \backend\components\DebtWidget::widget(['user' => Yii::$app->user->identity]); ?>
        </div>
        <div class="clearfix"></div>
        <?php /*
        <?= \backend\components\FuturePaymentsWidget::widget(['user' => Yii::$app->user->identity]); ?>
        */ ?>
    </div>

    <div class="list-group">
    <?php foreach ($pupilCollection as $pupil): ?>
        <a class="list-group-item" href="<?= \yii\helpers\Url::to(['money-history',  'id' => $pupil->id]); ?>"><?= $pupil->name; ?></a>
    <?php endforeach; ?>
    </div>
</div>
