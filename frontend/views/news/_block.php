<?php

/* @var $this \yii\web\View */
/* @var $news \common\models\News */

$newsUrl = Yii::$app->homeUrl . $news->webpage->url;

?>

<div class="item">
    <?php if ($news->image): ?>
        <div class="left">
            <div class="box">
                <h5 class="item-title"><a href="<?= $newsUrl; ?>"><?= $news->name; ?></a></h5>
                <a href="<?= $newsUrl; ?>" class="img d-block">
                    <img class="img-fluid" src="<?= $news->imageUrl; ?>" alt="<?= htmlentities($news->name, ENT_QUOTES); ?>">
                </a>
            </div>
        </div>
    <?php endif; ?>
    <div class="right">
        <?php if ($news->teasered): ?>
            <div class="desc">
                <?= $news->teaser; ?>
            </div>
            <a href="<?= $newsUrl; ?>" class="btn btn-danger btn-readmore btn-lg mt-3">Читать полностью</a>
        <?php else: ?>
            <?= $news->content; ?>
        <?php endif; ?>
    </div>
</div>
