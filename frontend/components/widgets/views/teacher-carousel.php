<?php
/* @var $teachers \common\models\Teacher[] */
/* @var $teachersWebpage \common\models\Webpage */
?>

<div class="row">
    <div class="col-xs-12">
        <h2 class="text-center text-uppercase">Наши специалисты</h2>
    </div>
</div>
<div class="row teacher-carousel-widget carousel-widget">
    <div class="col-xs-12">
        <div id="owl-carousel-teachers" class="owl-carousel owl-theme">
            <?php foreach ($teachers as $teacher): ?>
                <div class="teacher-carousel-item">
                    <a href="<?= Yii::$app->homeUrl . $teacher->webpage->url; ?>">
                        <img src="<?= $teacher->photo ? $teacher->imageUrl : $teacher->noPhotoUrl; ?>">
                        <div class="link-body">
                            <?= $teacher->officialName; ?><br><br class="hidden-xs">
                            <span><?= $teacher->title; ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="carousel-nav-teachers" class="carousel-nav compact-carousel-nav">
            <a href="<?= Yii::$app->homeUrl . $teachersWebpage->url; ?>" class="all-items-link">
                <span class="icon icon-teachers"></span>
                <span class="link-body">Все специалисты</span>
            </a>
        </div>
    </div>
    <div class="clearfix"></div>
</div>