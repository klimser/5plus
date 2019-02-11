<?php

/* @var $this \yii\web\View */
/* @var $post common\models\Promotion */
/* @var $webpage common\models\Webpage */
/* @var $blogWebpage common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Yii::$app->homeUrl . $blogWebpage->url, 'label' => 'Блог'];
$this->params['breadcrumbs'][] = $post->name;

$this->registerMetaTag(['name' => 'og:title', 'content' => $post->name]);
$this->registerMetaTag(['name' => 'og:type', 'content' => 'article']);
$this->registerMetaTag(['name' => 'og:url', 'content' => \yii\helpers\Url::to(\Yii::$app->homeUrl . $webpage->url, true)]);
$this->registerMetaTag(['name' => 'og:image', 'content' => $post->imageUrl]);

?>

<div class="row">
    <div class="col-xs-12 text-content">
        <?php if ($post->image): ?>
            <img src="<?= $post->imageUrl; ?>" class="pull-left">
        <?php endif; ?>
        <?= $post->content; ?>
    </div>
</div>