<?php

/* @var $this \yii\web\View */
/* @var $promotion \common\models\Promotion */
/* @var $grid bool */

?>
<div class="news-item <?php if ($grid): ?>col-xs-12 col-sm-6 col-md-3<?php endif; ?>">
    <a href="<?= Yii::$app->homeUrl . $promotion->webpage->url; ?>">
        <img src="<?= $promotion->imageUrl; ?>" class="mw-100">
        <div class="link-body"><?= $promotion->name; ?></div>
    </a>
</div>
