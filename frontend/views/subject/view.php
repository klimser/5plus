<?php

/* @var $this \frontend\components\extended\View */
/* @var $subject common\models\Subject */
/* @var $webpage common\models\Webpage */
/* @var $subjectsWebpage common\models\Webpage */
/* @var $quizCount int */
/* @var $quizWebpage \common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Yii::$app->homeUrl . $subject->subjectCategory->webpage->url, 'label' => $subject->subjectCategory->name];
$this->params['breadcrumbs'][] = $subject->name;
?>

<div class="container">
    <div class="content-box">
        <?php if ($quizCount): ?>
            <a class="btn btn-info btn-lg float-right m-l-3 m-b-3" href="<?= \yii\helpers\Url::to(['webpage', 'id' => $quizWebpage->id, 'subjectId' => $subject->id]); ?>">
                Узнайте свой уровень.
            </a>
        <?php endif; ?>
        <?= $subject->content; ?>
    </div>
</div>
