<?php

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $teachers \common\models\Teacher[] */
/* @var $hasMore bool */

$this->params['breadcrumbs'][] = 'Команда';
?>
    <div class="row teacher-index">
        <?php
        $i = 0;
        foreach ($teachers as $teacher):
            $i++;
            ?>
            <?= $this->render('/teacher/_block', ['teacher' => $teacher]); ?>

            <?php if ($i % 2 == 0): ?>
            <div class="clearfix"></div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php if ($hasMore): ?>
    <div class="row">
        <div class="col-xs-12 more-block">
            <a href="<?= Yii::$app->homeUrl . $webpage->url; ?>" onclick="return Main.loadMore(this, '.teacher-index', '.teacher-item');">
                <span class="icon icon-more"></span>
                <span class="link-body">Показать ещё</span>
            </a>
        </div>
    </div>
<?php endif; ?>