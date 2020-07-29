<?php
/* @var $this \yii\web\View */
/* @var $teachers \common\models\Teacher[] */
?>
<div class="clearfix"></div>
<hr>
<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <h3>Другие специалисты по данному предмету</h3>
        <div class="team-list">
            <?php foreach ($teachers as $teacher): ?>
                <?= $this->render('/teacher/_block', ['teacher' => $teacher]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
