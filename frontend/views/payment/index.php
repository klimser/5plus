<?php

use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */
/* @var $webpage \common\models\Webpage */

$this->params['breadcrumbs'][] = 'Онлайн оплата'; ?>

<div class="container">
    <div class="content-box">
        <div class="row">
            <div class="col-12 col-md-6 text-center">
                <p>
                    <a class="btn btn-lg btn-primary" href="<?= Url::to(['webpage', 'id' => $webpage->id, 'type' => 'pupil']); ?>">
                        Для учащихся
                    </a>
                </p>
            </div>
            <div class="col-12 col-md-6 text-center">
                <p>
                    <a class="btn btn-lg btn-primary" href="<?= Url::to(['webpage', 'id' => $webpage->id, 'type' => 'new']); ?>">
                        Для новых студентов
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
