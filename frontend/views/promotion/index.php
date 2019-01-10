<?php

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $promotions \common\models\Promotion[] */
/* @var $pager \yii\data\Pagination */

$this->params['breadcrumbs'][] = 'Акции';
?>
<div class="row news-index">
    <?php
    $i = 0;
    foreach ($promotions as $promotion):
        $i++;
    ?>
        <?= $this->render('/promotion/_block', ['promotion' => $promotion, 'grid' => true]); ?>

        <?php if ($i % 4 == 0): ?>
            <div class="clearfix"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<div class="text-center">
    <?= \yii\widgets\LinkPager::widget([
            'pagination' => $pager,
        'nextPageLabel' => '<span class="hidden-xs">Следующая страница</span> →',
        'prevPageLabel' => '← <span class="hidden-xs">Предыдущая страница</span>',
    ]); ?>
</div>
