<?php

use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */
/* @var $webpage \common\models\Webpage */

$this->registerJs(<<<SCRIPT
Main.initPhoneFormatted();
SCRIPT
);

$this->params['breadcrumbs'][] = 'Онлайн оплата'; ?>

<div class="row">
    <div class="col-xs-12 col-sm-6 text-center">
        <p>
            <a class="btn btn-lg btn-primary" href="<?= Url::to(['webpage', 'id' => $webpage->id, 'type' => 'pupil']); ?>">
                Для учащихся
            </a>
        </p>
    </div>
    <div class="col-xs-12 col-sm-6 text-center">
        <p>
            <a class="btn btn-lg btn-primary" href="<?= Url::to(['webpage', 'id' => $webpage->id, 'type' => 'new']); ?>">
                Для новых студентов
            </a>
        </p>
    </div>
</div>
