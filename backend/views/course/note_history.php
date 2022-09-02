<?php

/* @var $this yii\web\View */
/* @var $course common\models\Course */

$this->title = 'Группа: ' . $course->courseConfig->name;
$this->params['breadcrumbs'][] = ['label' => 'Темы', 'url' => ['notes']];
$this->params['breadcrumbs'][] = $course->courseConfig->name;
?>
<div class="row pb-3">
    <div class="col-12 text-center">
        Группа <b><?= $course->courseConfig->name; ?></b> <small class="text-muted">(<?= $course->subject->name; ?>)</small>
    </div>
</div>

<table class="table table-bordered table-sm">
    <?php foreach ($course->notes as $note): ?>
        <tr>
            <td><?= Yii::$app->formatter->asDatetime($note->createDate, 'php:j F Y H:i'); ?></td>
            <td><?= $note->topic; ?></td>
        </tr>
    <?php endforeach; ?>
</table>
