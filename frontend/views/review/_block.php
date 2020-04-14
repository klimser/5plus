<?php

/* @var $this \yii\web\View */
/* @var $review \common\models\Review */
/* @var $grid bool */

?>
<div class="item">
    <?php /*<div class="left">
        <div class="box">
            <div class="img">
                <img src="images/team-img1.jpg" alt="ava">
            </div>
            <div class="name">Лилия Адамян</div>
        </div>
    </div> */ ?>
    <div class="right">
        <div class="desc">
            <div class="text">
                <?= nl2br($review->message); ?>
            </div>
            <?php /*<div class="star">
                <img src="images/full-star-ico.png" alt="star">
                <img src="images/full-star-ico.png" alt="star">
                <img src="images/full-star-ico.png" alt="star">
                <img src="images/full-star-ico.png" alt="star">
                <img src="images/full-star-ico.png" alt="star">
            </div>*/ ?>
        </div>
        <div class="who">
            <div class="ico"><span><?= mb_substr($review->name, 0, 1, 'UTF-8'); ?></span></div>
            <div class="text"><?= $review->name; ?></div>
        </div>
    </div>
</div>
