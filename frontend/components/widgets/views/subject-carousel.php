<?php
/* @var $subjectCategory \common\models\SubjectCategory */
/* @var $buttonLeft bool */
/* @var $index int */
?>

<div class="row subject-carousel-widget carousel-widget">
    <div class="carousel-block col-xs-12 col-sm-8 col-md-9 <?= $buttonLeft ? 'col-sm-push-4 col-md-push-3' : ''; ?>">
        <div id="carousel-nav-<?= $index; ?>" class="carousel-nav"></div>
        <div id="owl-carousel-<?= $index; ?>" class="owl-carousel owl-theme">
            <?php foreach ($subjectCategory->activeSubjects as $subject): ?>
                <div class="carousel-item">
                    <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>">
                        <img src="<?= $subject->imageUrl; ?>"><br>
                        <span class="link-body"><?= $subject->name; ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="info-block col-xs-12 col-sm-4 col-md-3 <?= $buttonLeft ? 'col-sm-pull-8 col-md-pull-9' : ''; ?>">
        <div class="widget-light"><?= $subjectCategory->name; ?></div>
        <a href="#" class="widget-button" onclick="MainPage.launchModal(); return false;">Записаться на курс</a>
    </div>
    <div class="clearfix"></div>
</div>