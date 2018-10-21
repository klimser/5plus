<?php

/* @var $this \yii\web\View */
/* @var $teacher \common\models\Teacher */

?>
<div class="col-xs-12 col-sm-6 teacher-item">
    <div class="teacher-page-item">
        <a href="<?= Yii::$app->homeUrl . $teacher->webpage->url; ?>">
            <img src="<?= $teacher->photo ? $teacher->imageUrl : $teacher->noPhotoUrl; ?>" class="max-width-100">
            <div class="link-body">
                <?= $teacher->officialName; ?><br><br>
                <span><?= $teacher->title; ?></span>
            </div>
        </a>
    </div>
</div>