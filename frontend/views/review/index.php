<?php

use common\components\DefaultValuesComponent;
use himiklab\yii2\recaptcha\ReCaptcha2;
use yii\bootstrap4\Html;
use common\components\WidgetHtml;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $reviews \common\models\Review[] */
/* @var $pager \yii\data\Pagination */

$this->params['breadcrumbs'][] = 'Отзывы';
?>
<div class="container">
    <div class="row align-items-center">
        <?php if (YII_ENV == 'prod'): ?>
            <div class="col">
                <?= WidgetHtml::getByName('review_link_widget'); ?>
            </div>
        <?php endif; ?>
        <div class="col">
            <div class="text-right">
                <a class="btn btn-lg btn-danger " href="#" onclick="Review.launchModal(); return false;">+ написать отзыв</a>
            </div>
        </div>
    </div>
    <div class="reviews-list">
    <?php foreach ($reviews as $review): ?>
        <?= $this->render('_block', ['review' => $review, 'grid' => true]); ?>
    <?php endforeach; ?>
    </div>
</div>

<nav class="pagination-box">
    <?= LinkPager::widget(
        array_merge(
            DefaultValuesComponent::getPagerSettings(),
            ['pagination' => $pager, 'maxButtonCount' => 4,]
        )
    ); ?>
</nav>

<div id="review_form" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= Html::beginForm(Url::to(['review/create']), 'post', ['onsubmit' => 'return Review.complete(this);']); ?>
            <div class="modal-header">
                <h5 class="modal-title">Оставить отзыв</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="review_form_body" class="collapse show">
                    <div class="form-group">
                        <label for="review-name">Ваше имя</label>
                        <input name="review[name]" id="review-name" class="form-control" required minlength="2" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="review-message">Ваш отзыв</label>
                        <textarea name="review[message]" id="review-message" class="form-control" required maxlength="1000"></textarea>
                    </div>
                    <?= ReCaptcha2::widget(['name' => 'review[reCaptcha]']) ?>
                </div>
                <div id="review_form_extra" class="collapse"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                <button class="btn btn-primary">отправить</button>
            </div>
            <?= Html::endForm(); ?>
        </div>
    </div>
</div>
