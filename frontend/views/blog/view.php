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

<div class="container">
    <div class="content-box">
        <?php if ($post->image): ?>
            <img src="<?= $post->imageUrl; ?>" class="img-fluid float-left mw-sm-50 mr-3 mb-3" alt="<?= $post->name; ?>">
        <?php endif; ?>
        <?= $post->content; ?>
    </div>
</div>
