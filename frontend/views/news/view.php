<?php

use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $news common\models\News */
/* @var $webpage common\models\Webpage */
/* @var $newsWebpage common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Yii::$app->homeUrl . $newsWebpage->url, 'label' => 'Новости'];
$this->params['breadcrumbs'][] = $news->name;

$this->registerMetaTag(['name' => 'og:title', 'content' => $news->name]);
$this->registerMetaTag(['name' => 'og:type', 'content' => 'article']);
$this->registerMetaTag(['name' => 'og:url', 'content' => Url::to(\Yii::$app->homeUrl . $webpage->url, true)]);
$this->registerMetaTag(['name' => 'og:image', 'content' => $news->imageUrl]);

?>

<div class="container">
    <div class="content-box">
        <?php if ($news->image): ?>
            <img src="<?= $news->imageUrl; ?>" class="img-fluid float-left mw-sm-50 mr-3 mb-3" alt="<?= htmlentities($news->name, ENT_QUOTES); ?>">
        <?php endif; ?>
        <?= $news->content; ?>
    </div>
</div>
