<?php

/* @var $this \yii\web\View */
/* @var $subject \common\models\Subject */

?>
<div class="col-xs-12 col-sm-6 subject-item">
    <?php if ($subject->image): ?>
        <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>" class="pull-left max-width-50 top-left">
            <img src="<?= $subject->imageUrl; ?>" class="max-width-100">
        </a>
    <?php endif; ?>
    <h2><a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>"><?= $subject->name; ?></a></h2>
    <p><?= nl2br($subject->description); ?></p>
    <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>">
        <span class="icon icon-details"></span>
        <span class="link-body">Подробнее</span>
    </a>
</div>