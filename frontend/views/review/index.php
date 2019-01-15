<?php

use yii\bootstrap\Html;
use common\components\WidgetHtml;

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $reviews \common\models\Review[] */
/* @var $pager \yii\data\Pagination */

$this->params['breadcrumbs'][] = 'Отзывы';

$this->registerJs('Review.init();');
if (YII_ENV == 'prod') {
    $this->params['h1'] .= WidgetHtml::getByName('review_link_widget');
}

?>
<div class="row review-index">
    <?php foreach ($reviews as $review): ?>
        <?= $this->render('_block', ['review' => $review, 'grid' => true]); ?>
    <?php endforeach; ?>
</div>

<a id="add-review-button" href="#" onclick="Review.launchModal(); return false;">+</a>

<div class="text-center">
    <?= \yii\widgets\LinkPager::widget([
        'pagination' => $pager,
        'nextPageLabel' => '<span class="hidden-xs">Следующая страница</span> →',
        'prevPageLabel' => '← <span class="hidden-xs">Предыдущая страница</span>',
    ]); ?>
</div>
</div>

<div id="review_form" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= Html::beginForm(\yii\helpers\Url::to(['review/create']), 'post', ['onsubmit' => 'return Review.complete(this);']); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title">Оставить отзыв</h4>
            </div>
            <div class="modal-body">
                <div id="review_form_body">
                    <div class="form-group">
                        <label for="review-name">Ваше имя</label>
                        <input name="review[name]" id="review-name" class="form-control" required minlength="2" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="review-message">Ваш отзыв</label>
                        <textarea name="review[message]" id="review-message" class="form-control" required maxlength="1000"></textarea>
                    </div>
                    <?= \himiklab\yii2\recaptcha\ReCaptcha::widget(['name' => 'review[reCaptcha]']) ?>
                </div>
                <div id="review_form_extra" class="hidden"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                <button class="btn btn-primary">отправить</button>
            </div>
            <?= Html::endForm(); ?>
        </div>
    </div>
</div>
<div class="container">
