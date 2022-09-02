<?php

use common\components\helpers\Html;
use common\models\User;
use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */
/* @var $users User[] */
/* @var $webpage \common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Url::to(['webpage', 'id' => $webpage->id]), 'label' => 'Онлайн оплата'];
$this->params['breadcrumbs'][] = 'Выбрать пользователя';

?>

<div class="container">
    <div class="content-box">
        <h1>Выберите студента (родителя)</h1>
        <p>По указанному номеру телефона найдено несколько студентов (родителей). Выберите за кого вы хотите внести оплату.</p>
        <div class="row">
            <div id="user_select" class="col-12 col-auto mb-3">
                <?= $form = Html::beginForm(Url::to(['payment/select-user'])); ?>
                    <?php foreach ($users as $user): ?>
                        <button name="user-<?= $user->id; ?>" class="btn btn-lg btn-outline-dark student-button mb-2"><?= $user->nameHidden; ?></button><br>
                    <?php endforeach; ?>
                <?= Html::endForm(); ?>
            </div>
        </div>
    </div>
</div>
