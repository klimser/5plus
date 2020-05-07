<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $pupilCollection \common\models\User[] */
/* @var $user \common\models\User */
/* @var $month string */

if (Yii::$app->user->identity->role == \common\models\User::ROLE_ROOT) $this->title = 'Студенты';
else $this->title = 'Мои дети';

$this->params['breadcrumbs'][] = $this->title;




?>
<div class="user_list">
    <div class="row">
        <div class="col-xs-12">
            <h1 class="float-left mt-0"><?= Html::encode($this->title) ?></h1>
            <?= \backend\components\DebtWidget::widget(['user' => Yii::$app->user->identity]); ?>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="list-group">
    <?php foreach ($pupilCollection as $pupil): ?>
        <a class="list-group-item" href="<?= \yii\helpers\Url::to(['schedule',  'id' => $pupil->id, 'month' => $month]); ?>"><?= $pupil->name; ?></a>
    <?php endforeach; ?>
    </div>
</div>
