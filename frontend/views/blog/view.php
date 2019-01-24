<?php

/* @var $this \yii\web\View */
/* @var $post common\models\Promotion
/* @var $webpage common\models\Webpage */
/* @var $blogWebpage common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Yii::$app->homeUrl . $blogWebpage->url, 'label' => 'Блог'];
$this->params['breadcrumbs'][] = $post->name;
?>

<div class="row">
    <div class="col-xs-12 text-content">
        <?php if ($post->image): ?>
            <img src=""
        <?php endif; ?>
        <?= $post->content; ?>
    </div>
</div>