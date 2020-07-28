<?php

/* @var $this \yii\web\View */
/* @var $promotion \common\models\Promotion */
/* @var $grid bool */

?>
<div class="news-item <?php if ($grid): ?>col-12 col-md-6 col-lg-3<?php endif; ?>">
    <a href="<?= Yii::$app->homeUrl . $promotion->webpage->url; ?>">
        <img src="<?= $promotion->imageUrl; ?>" class="img-fluid">
        <div class="link-body"><?= $promotion->name; ?></div>
    </a>
</div>
