<?php

/* @var $this \yii\web\View */
/* @var $post \common\models\Promotion */
/* @var $grid bool */

?>
<div class="news-item <?php if ($grid): ?>col-xs-12 col-sm-6 col-md-3<?php endif; ?>">
    <a href="<?= Yii::$app->homeUrl . $post->webpage->url; ?>">
        <img src="<?= $post->imageUrl; ?>" class="max-width-100">
        <div class="link-body"><?= $post->name; ?></div>
    </a>
</div>