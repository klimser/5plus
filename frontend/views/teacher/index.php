<?php

use common\components\DefaultValuesComponent;
use yii\widgets\LinkPager;

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $teachers \common\models\Teacher[] */
/* @var $pager yii\data\Pagination */

$this->params['breadcrumbs'][] = 'Команда';
?>
<div class="container">
    <div class="team-list">
        <?php foreach ($teachers as $teacher): ?>
            <?= $this->render('/teacher/_block', ['teacher' => $teacher]); ?>
        <?php endforeach; ?>
    </div>
    <nav class="pagination-box">
        <?= LinkPager::widget(
            array_merge(
                DefaultValuesComponent::getPagerSettings(),
                ['pagination' => $pager, 'maxButtonCount' => 4]
            )
        ); ?>
    </nav>
</div>
