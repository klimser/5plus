<?php

/* @var $this \yii\web\View */
/* @var $teacher \common\models\Teacher */

?>
<div class="item">
    <a href="<?= Yii::$app->homeUrl . $teacher->webpage->url; ?>" class="card">
        <span class="img"><span class="frame"><img src="<?= $teacher->photo ? $teacher->imageUrl : $teacher->noPhotoUrl; ?>" alt="<?= $teacher->officialName; ?>"></span></span>
        <span class="text">
            <span class="name"><?= $teacher->officialName; ?></span>
            <span class="desc"><?= $teacher->title; ?></span>
        </span>
    </a>
</div>
