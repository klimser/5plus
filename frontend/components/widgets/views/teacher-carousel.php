<?php
/* @var $teachers \common\models\Teacher[] */
/* @var $teachersWebpage \common\models\Webpage */
?>

<section class="our-specialists-box">
    <div class="container">
        <div class="shadow-title">НАШИ СПЕЦИАЛИСТЫ</div>
        <h2 class="block-title">НАШИ СПЕЦИАЛИСТЫ</h2>
        <div class="swiper-our-specialists-slider swiper-container">
            <div class="swiper-wrapper">
                <?php foreach ($teachers as $teacher): ?>
                    <div class="swiper-slide item">
                        <div class="card">
                            <a href="<?= Yii::$app->homeUrl . $teacher->webpage->url; ?>" class="img">
                                <img src="<?= $teacher->photo ? $teacher->imageUrl : $teacher->noPhotoUrl; ?>" alt="<?= $teacher->officialName; ?>">
                            </a>
                            <a href="<?= Yii::$app->homeUrl . $teacher->webpage->url; ?>" class="text">
                                <span class="name"><?= $teacher->officialName; ?></span>
                                <span class="desc"><?= $teacher->title; ?></span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="swiper-button-prev"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
        <div class="swiper-button-next"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
    </div>
</section>
