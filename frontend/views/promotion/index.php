<?php

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $promotions \common\models\Promotion[] */
/* @var $pager \yii\data\Pagination */

$this->params['breadcrumbs'][] = 'Акции';
?>
<div class="container">
    <div class="received-box">
        <div class="left">
            <div class="block-img"><img src="<?= $promotions[0]->imageUrl; ?>" alt="image"></div>
            <h3 class="block-name"><?= $promotions[0]->name;?></h3>
        </div>
        <div class="right">
            <div class="oh">
                <div class="swiper-received-slider swiper-container">
                    <div class="swiper-wrapper">
                        <?= $promotions[0]->content; ?>
                    </div>
                </div>
            </div>
            <div class="swiper-button-prev"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
            <div class="swiper-button-next"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
        </div>
    </div>
    <div class="ielts-box">
        <div class="left">
            <div class="block-img"><img src="<?= $promotions[1]->imageUrl; ?>" alt="image"></div>
            <h3 class="block-name"><?= $promotions[1]->name;?></h3>
        </div>
        <div class="right">
            <div class="oh">
                <div class="swiper-ielts-slider swiper-container">
                    <div class="swiper-wrapper">
                        <?= $promotions[1]->content; ?>
                    </div>
                </div>
            </div>
            <div class="swiper-button-prev"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
            <div class="swiper-button-next"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
        </div>
    </div>
</div>
