<?php

use backend\models\WelcomeLesson;

/* @var $this yii\web\View */
/* @var $pupil \common\models\User */

?>
<div class="welcome_lessons m-t-10">
    <?php if (count($pupil->welcomeLessons) > 0): ?>
        <table class="table table-bordered table-condensed">
            <tr>
                <th>Группа, предмет</th>
                <th>Дата</th>
                <th>Статус</th>
                <th></th>
            </tr>
            <?php foreach ($pupil->welcomeLessons as $welcomeLesson): ?>
                <tr class="welcome-row" data-key="<?= $welcomeLesson->id; ?>" data-status="<?= $welcomeLesson->status; ?>" data-deny-reason="<?= $welcomeLesson->deny_reason; ?>" data-date="<?= $welcomeLesson->lessonDateTime->format('d.m.Y'); ?>">
                    <td>
                        <?php if ($welcomeLesson->group_id): ?>
                            <?= $welcomeLesson->group->name; ?><br>
                        <?php endif; ?>
                        <?= $welcomeLesson->subject->name; ?><br>
                        <?= $welcomeLesson->teacher->name; ?>
                    </td>
                    <td><?= $welcomeLesson->lessonDateTime->format('d.m.Y'); ?></td>
                    <td><?= WelcomeLesson::STATUS_LABELS[$welcomeLesson->status]; ?></td>
                    <td class="buttons-column"></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<button type="button" class="btn btn-success" onclick="User.addWelcomeLesson(undefined, $(this).closest('.user-view'));"><span class="fas fa-plus"></span> добавить</button>
