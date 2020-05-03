<?php
/* @var $reviews \common\models\Review[] */
/* @var $reviewsWebpage \common\models\Webpage */
?>

<section class="reviews-box">
    <div class="container">
        <div class="shadow-title">Отзывы</div>
        <h2 class="block-title">Отзывы</h2>
        <div class="swiper-reviews-slider swiper-container">
            <div class="swiper-wrapper">
                <?php foreach ($reviews as $review): ?>
                    <div class="swiper-slide item">
                        <blockquote class="blockquote">
                            <div class="text">
                                <p><?= nl2br($review->message); ?></p>
                            </div>
                            <footer class="blockquote-footer">
                                <?php /*<img src="images/reviews-ava1.jpg" alt="avatar" class="ava">*/ ?>
                                <div class="author">
                                    <div class="name"><?= $review->name; ?></div>
                                    <?php /*<div class="who">Ceo at Apple Inc</div>*/ ?>
                                </div>
                            </footer>
                        </blockquote>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
        <div class="swiper-button-prev"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/third-slider-arr.png" alt="arrow"></div>
        <div class="swiper-button-next"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/third-slider-arr.png" alt="arrow"></div>
    </div>
</section>
