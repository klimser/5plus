<?php

/* @var $this \yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $subjectCategory \common\models\SubjectCategory */
/* @var $hasMore bool */

$this->params['breadcrumbs'][] = $subjectCategory->name;
?>
<div class="row subject-index">
    <?php
    $i = 0;
    foreach ($subjectCategory->activeSubjects as $subject):
        $i++;
    ?>
        <?= $this->render('/subject/_block', ['subject' => $subject]); ?>

        <?php if ($i % 2 == 0): ?>
            <div class="clearfix"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>