<?php

use backend\models\WelcomeLesson;
use common\models\User;

/* @var $this yii\web\View */
/* @var $pupil User */
$printable = false;
?>
<div class="welcome_lessons mt-2">
    <?php if (count($pupil->welcomeLessons) > 0): ?>
        <table class="table table-bordered table-sm welcome-table">
            <tr>
                <th>Группа, предмет</th>
                <th>Дата</th>
                <th>Статус</th>
                <th></th>
            </tr>
            <?php foreach ($pupil->welcomeLessons as $welcomeLesson):
                if (WelcomeLesson::STATUS_UNKNOWN == $welcomeLesson->status) {
                    $printable = true;
                }
            ?>
                <tr class="welcome-row" data-key="<?= $welcomeLesson->id; ?>" data-status="<?= $welcomeLesson->status; ?>" data-deny-reason="<?= $welcomeLesson->deny_reason; ?>" data-date="<?= $welcomeLesson->lessonDateTime->format('d.m.Y'); ?>">
                    <td>
                        <?php if ($welcomeLesson->course_id): ?>
                            <?= $welcomeLesson->course->name; ?><br>
                            <?= $welcomeLesson->course->subject->name; ?><br>
                            <?= $welcomeLesson->course->teacher->name; ?>
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
                        <?php if ($welcomeLesson->comments): ?>
                            <br><br><p class="small">
                            <?php foreach ($welcomeLesson->comments as $comment): ?>
                                <i class="small">
                                    <?= User::getNameById($comment['admin_id']); ?><br>
                                    <?= $comment['date']; ?>

                                </i><br>
                                <?= $comment['text']; ?><br>
                                -<br>
                            <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </td>
                    <td class="buttons-column"></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<div class="row">
    <div class="col">
        <button type="button" class="btn btn-success" onclick="User.addWelcomeLesson(undefined, $(this).closest('.user-view'));"><span class="fas fa-plus"></span> добавить</button>
    </div>
    <div class="col text-right">
        <?php if ($printable): ?>
            <button type="button" class="btn btn-info" onclick="Dashboard.printWelcomeLessonInfo(this);">Распечатать памятку</button>
        <?php endif; ?>
    </div>
</div>
