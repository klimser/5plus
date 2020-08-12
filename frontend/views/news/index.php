<?php

use common\components\DefaultValuesComponent;
use yii\widgets\LinkPager;

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $newsList \common\models\News[] */
/* @var $pager \yii\data\Pagination */

$this->params['breadcrumbs'][] = 'Новости';

?>
<div class="container">
    <div class="news-list">
        <?php foreach ($newsList as $news): ?>
            <?= $this->render('/news/_block', ['news' => $news]); ?>
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
