<?php
/* @var $this \yii\web\View */
/* @var $teachers \common\models\Teacher[] */
?>
<hr>
<div class="row">
    <div class="col-xs-12">
        <div class="title-separator">Другие специалисты по данному предмету</div>
        <div class="row">
            <?php foreach ($teachers as $teacher): ?>
                <?= $this->render('/teacher/_block', ['teacher' => $teacher]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>