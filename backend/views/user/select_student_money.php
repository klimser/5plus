<?php

use yii\bootstrap4\Html;

/* @var $this yii\web\View */
/* @var $studentCollection \common\models\User[] */
/* @var $user \common\models\User */

$this->title = 'Мои дети';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="user_list">
    <div class="row">
        <div class="col-12">
            <h1 class="float-left mt-0"><?= Html::encode($this->title) ?></h1>
            <?= \backend\components\DebtWidget::widget(['user' => Yii::$app->user->identity]); ?>
        </div>
        <div class="clearfix"></div>
        <?php /*
        <?= \backend\components\FuturePaymentsWidget::widget(['user' => Yii::$app->user->identity]); ?>
        */ ?>
    </div>

    <div class="list-group">
    <?php foreach ($studentCollection as $student): ?>
        <a class="list-group-item" href="<?= \yii\helpers\Url::to(['money-history',  'id' => $student->id]); ?>"><?= $student->name; ?></a>
    <?php endforeach; ?>
    </div>
</div>
