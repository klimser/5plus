<?php

/* @var $this \yii\web\View */
/* @var $post \common\models\Blog */

$postUrl = Yii::$app->homeUrl . $post->webpage->url;

?>

<div class="item">
    <?php if ($post->image): ?>
        <div class="left">
            <div class="box">
                <a href="<?= $postUrl; ?>" class="img">
                    <img src="<?= $post->imageUrl; ?>" alt="<?= htmlentities($post->name, ENT_QUOTES); ?>">
                </a>
            </div>
        </div>
    <?php endif; ?>
    <div class="right">
        <h5 class="title"><a href="<?= $postUrl; ?>"><?= $post->name; ?></a></h5>
        <div class="desc">
            <?= $post->teaser; ?>
        </div>
        <?php if ($post->teasered): ?>
            <a href="<?= $postUrl; ?>" class="btn btn-danger btn-readmore btn-lg">Читать полностью</a>
        <?php endif; ?>
    </div>
</div>
