<?php

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $posts \common\models\Blog[] */
/* @var $pager \yii\data\Pagination */

$this->params['breadcrumbs'][] = 'Блог';
?>
<div class="container">
    <div class="blog-list">
        <?php foreach ($posts as $post): ?>
            <?= $this->render('/blog/_block', ['post' => $post]); ?>
        <?php endforeach; ?>
    </div>
</div>

<nav class="pagination-box">
    <?= \yii\widgets\LinkPager::widget(
        array_merge(
            \common\components\DefaultValuesComponent::getPagerSettings(),
            ['pagination' => $pager, 'maxButtonCount' => 4,]
        )
    ); ?>
</nav>
