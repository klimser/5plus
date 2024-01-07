<?php

use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */
/* @var $subject common\models\Subject */
/* @var $webpage common\models\Webpage */
/* @var $subjectsWebpage common\models\Webpage */
/* @var $quizCount int */
/* @var $quizWebpage \common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Yii::$app->homeUrl . $subject->subjectCategory->webpage->url, 'label' => $subject->subjectCategory->name];
$this->params['breadcrumbs'][] = $subject->name['ru'];
?>

<div class="container">
    <div class="content-box subject-page text-md-justify">
        <?php if ($quizCount): ?>
            <a class="btn btn-info btn-lg float-right w-100 w-md-25 w-lg-auto mw-lg-25 ml-md-1 ml-lg-3 mb-3" href="<?= Url::to(['webpage', 'id' => $quizWebpage->id, 'subjectId' => $subject->id]); ?>">
                Узнайте свой уровень.
            </a>
        <?php endif; ?>
        <?= $subject->content; ?>
    </div>
</div>
