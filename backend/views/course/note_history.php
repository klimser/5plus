<?php

/* @var $this yii\web\View */
/* @var $group common\models\Course */

$this->title = 'Группа: ' . $group->name;
$this->params['breadcrumbs'][] = ['label' => 'Темы', 'url' => ['notes']];
$this->params['breadcrumbs'][] = $group->name;
?>
<div class="row pb-3">
    <div class="col-12 text-center">
        Группа <b><?= $group->name; ?></b> <small class="text-muted">(<?= $group->subject->name; ?>)</small>
    </div>
</div>

<table class="table table-bordered table-sm">
    <?php foreach ($group->notes as $note): ?>
        <tr>
            <td><?= Yii::$app->formatter->asDatetime($note->createDate, 'php:j F Y H:i'); ?></td>
            <td><?= $note->topic; ?></td>
        </tr>
    <?php endforeach; ?>
</table>
