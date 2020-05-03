<?php

/* @var $this \yii\web\View */
/* @var $promotion common\models\Promotion
/* @var $webpage common\models\Webpage */
/* @var $promotionsWebpage common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Yii::$app->homeUrl . $promotionsWebpage->url, 'label' => 'Акции'];
$this->params['breadcrumbs'][] = $promotion->name;
?>

<div class="container">
    <div class="content-box">
        <?= $promotion->content; ?>
    </div>
</div>
