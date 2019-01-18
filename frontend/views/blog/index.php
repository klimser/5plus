<?php

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $posts \common\models\Blog[] */
/* @var $pager \yii\data\Pagination */

$this->params['breadcrumbs'][] = 'Блог';
?>
<div class="news-index">
    <?php
    $first = true;
    foreach ($posts as $post): ?>
        <?= $this->render('/blog/_block', ['post' => $post, 'first' => $first]); ?>
    <?php
        $first = false;
    endforeach; ?>
</div>

<div class="text-center">
    <?= \yii\widgets\LinkPager::widget([
        'pagination' => $pager,
        'nextPageLabel' => '<span class="hidden-xs">Следующая страница</span> →',
        'prevPageLabel' => '← <span class="hidden-xs">Предыдущая страница</span>',
    ]); ?>
</div>
