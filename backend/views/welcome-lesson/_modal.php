<div class="modal fade" id="welcome-lesson-moving-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="welcome-lesson-moving-form" onsubmit="WelcomeLesson.movePupil(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">В группу!</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="welcome-lesson-messages-place"></div>
                    <h3 class="pupil-info"></h3>
                    <p>Дата начала занятий: <b class="welcome-lesson-start-date"></b></p>
                    <input type="hidden" name="welcome_lesson[id]" class="welcome-lesson-id" required>
                    <b>Группа</b>
                    <div class="group-proposal"></div>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input class="form-check-input" type="radio" name="welcome_lesson[group_proposal]" value="0" onchange="WelcomeLesson.groupChange(this);">
                            Другая
                        </label>
                    </div>
                    <select name="welcome_lesson[group_id]" class="form-control other-group" disabled></select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary">В группу!</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="welcome-lesson-reschedule-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="welcome-lesson-reschedule-form" onsubmit="WelcomeLesson.reschedule(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Перенести</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="welcome-lesson-reschedule-messages-place"></div>
                    <h3 class="pupil-info"></h3>
                    <p>Дата занятия: <b class="welcome-lesson-date"></b></p>
                    <input type="hidden" name="welcome_lesson[id]" class="welcome-lesson-id" required>
                    <div class="form-group">
                        <label>Новая дата</label>
                        <input type="text" class="form-control date-select datepicker" name="welcome_lesson[date]" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary">перенести</button>
                </div>
            </form>
        </div>
    </div>
</div>
