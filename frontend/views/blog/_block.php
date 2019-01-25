<?php

/* @var $this \yii\web\View */
/* @var $post \common\models\Blog */
/* @var $first bool */

$postUrl = Yii::$app->homeUrl . $post->webpage->url;

?>

<?php if (!$first): ?><hr><?php endif; ?>

<div class="row">
    <div class="col-xs-12">
        <h2>
            <a href="<?= $postUrl; ?>"><?= $post->name; ?></a>
        </h2>
    </div>
    <?php if ($post->image): ?>
        <div class="col-xs-12 col-md-3">
            <a href="<?= $postUrl; ?>">
                <img src="<?= $post->imageUrl; ?>" class="max-width-100">
            </a>
        </div>
    <?php endif; ?>
    <div class="col-xs-12 col-md-<?= $post->image ? 9 : 12; ?>">
        <?= $post->teaser; ?>
        <?php if ($post->teasered): ?>
            <br><br>
            <a href="<?= $postUrl; ?>">Читать полностью</a>
        <?php endif; ?>
    </div>
</div>