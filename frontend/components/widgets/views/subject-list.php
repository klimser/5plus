<?php
/* @var $subjectCategories \common\models\SubjectCategory[] */
/* @var $buttonLeft bool */
/* @var $index int */
?>

<h3>Наши крусы:</h3>
<div class="row">
    <?php foreach ($subjectCategories as $subjectCategory): ?>
        <div class="col-12 col-md-6 col-lg-3">
            <h4><?= $subjectCategory->name; ?></h4>
            <ul class="list-unstyled">
                <?php foreach ($subjectCategory->activeSubjects as $subject): ?>
                    <li>
                        <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>"><?= $subject->name; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>
