<?php

/* @var $this \yii\web\View */
/* @var $teacher common\models\Teacher */
/* @var $webpage common\models\Webpage */
/* @var $teachersWebpage common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Yii::$app->homeUrl . $teachersWebpage->url, 'label' => 'Команда'];
$this->params['breadcrumbs'][] = $teacher->officialName;

$this->registerMetaTag(['name' => 'og:title', 'content' => $teacher->officialName]);
$this->registerMetaTag(['name' => 'og:type', 'content' => 'profile']);
$this->registerMetaTag(['name' => 'og:url', 'content' => \yii\helpers\Url::to(\Yii::$app->homeUrl . $webpage->url, true)]);
$this->registerMetaTag(['name' => 'og:image', 'content' => $teacher->imageUrl]);

use frontend\components\widgets\TeacherSubjectWidget; ?>

<div class="container">
    <div class="content-box teacher-page">
        <?php if ($teacher->photo): ?>
            <img src="<?= $teacher->imageUrl; ?>" class="float-left mw-50 mr-3 mb-3" alt="<?= $teacher->officialName; ?>">
        <?php endif; ?>
        
        <h2><?= $teacher->title; ?></h2>
        
        <?php if ($teacher->subjects): ?>
            <dl>
                <dt>Предметы</dt>
                <?php foreach ($teacher->subjects as $subject): ?>
                    <dd><?= $subject->name['ru']; ?></dd>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <p><?= $teacher->description; ?></p>

        <?= TeacherSubjectWidget::widget(['teacher' => $teacher]); ?>
    </div>
</div>
