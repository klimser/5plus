<?php

use backend\models\WelcomeLesson;

/* @var $this yii\web\View */
/* @var $pupil \common\models\User */

$this->registerJs(<<<SCRIPT
WelcomeLesson.init();
WelcomeLesson.fillTableButtons("table tr.welcome-row");
SCRIPT
);

?>
<div id="welcome_lessons" style="margin-top: 10px;">
    <?php if (count($pupil->welcomeLessons) > 0): ?>
        <table class="table table-bordered table-condensed">
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <?php foreach ($pupil->welcomeLessons as $welcomeLesson): ?>
                <tr class="welcome-row" data-key="<?= $welcomeLesson->id; ?>" data-status="<?= $welcomeLesson->status; ?>" data-deny-reason="<?= $welcomeLesson->deny_reason; ?>">
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
<div class="modal fade" id="moving-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="moving-form" onsubmit="return WelcomeLesson.movePupil(this);">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">В группу!</h4>
                </div>
                <div class="modal-body">
                    <div id="modal_messages_place"></div>
                    <div id="start_date"></div>
                    <input type="hidden" name="id" id="lesson_id" required>
                    <b>Группа</b>
                    <div id="group_proposal"></div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="group_proposal" value="0" onchange="WelcomeLesson.groupChange(this);"> Другая
                        </label>
                    </div>
                    <select name="group_id" id="other_group" class="form-control" disabled></select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary">В группу!</button>
                </div>
            </form>
        </div>
    </div>
</div>
<button type="button" class="btn btn-success" onclick="User.addWelcomeLesson();"><span class="fas fa-plus"></span> добавить</button>
