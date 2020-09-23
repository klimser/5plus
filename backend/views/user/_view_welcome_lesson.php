<?php

use backend\models\WelcomeLesson;

/* @var $this yii\web\View */
/* @var $pupil \common\models\User */

?>
<div class="welcome_lessons mt-2">
    <?php if (count($pupil->welcomeLessons) > 0): ?>
        <table class="table table-bordered table-sm">
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
                            <?= $welcomeLesson->group->subject->name; ?><br>
                            <?= $welcomeLesson->group->teacher->name; ?>
                        <?php endif; ?>
                        
                    </td>
                    <td><?= $welcomeLesson->lessonDateTime->format('d.m.Y'); ?></td>
                    <td>
                        <?= WelcomeLesson::STATUS_LABELS[$welcomeLesson->status]; ?>
                        <?php if ($welcomeLesson->status === WelcomeLesson::STATUS_DENIED && $welcomeLesson->deny_reason): ?>
                            <br><b>Причина</b> <?= $welcomeLesson::DENY_REASON_LABELS[$welcomeLesson->deny_reason]; ?>
                            <?php if ($welcomeLesson->comment): ?>
                                <br><i><?= $welcomeLesson->comment; ?></i>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="buttons-column"></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<button type="button" class="btn btn-success" onclick="User.addWelcomeLesson(undefined, $(this).closest('.user-view'));"><span class="fas fa-plus"></span> добавить</button>
