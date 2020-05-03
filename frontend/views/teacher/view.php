<?php

/* @var $this \yii\web\View */
/* @var $teacher common\models\Teacher */
/* @var $webpage common\models\Webpage */
/* @var $teachersWebpage common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Yii::$app->homeUrl . $teachersWebpage->url, 'label' => 'Команда'];
$this->params['breadcrumbs'][] = $teacher->officialName;
?>

<div class="container">
    <div class="content-box">
        <?php if ($teacher->photo): ?>
            <img src="<?= $teacher->imageUrl; ?>" class="float-left mw-50 top-left">
        <?php endif; ?>
        
        <h2><?= $teacher->title; ?></h2>
        
        <?php if ($teacher->subjects): ?>
            <dl>
                <dt>Предметы</dt>
                <?php foreach ($teacher->subjects as $subject): ?>
                    <dd><?= $subject->name; ?></dd>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <p><?= $teacher->description; ?></p>
        
    </div>
</div>
<?php // \frontend\components\widgets\TeacherSubjectWidget::widget(['teacher' => $teacher]); ?>
