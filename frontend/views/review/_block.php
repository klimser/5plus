<?php

/* @var $this \yii\web\View */
/* @var $review \common\models\Review */
/* @var $grid bool */

?>
<div class="review-item <?php if ($grid): ?>col-xs-12<?php endif; ?>">
    <div class="review-header">
        <div class="pull-left"><?= $review->name; ?></div>
        <div class="pull-right">
            <span class="icon icon-calendar"></span>
            <?= $review->createDate->format('d.m.Y'); ?>
        </div>
        <div class="clearfix"></div>
    </div>
    <p class="review-body">
        <?= nl2br($review->message); ?>
    </p>
    <div class="read-more-block"></div>
</div>