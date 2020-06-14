<div class="modal fade" id="welcome-lesson-moving-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="welcome-lesson-moving-form" onsubmit="WelcomeLesson.movePupil(this); return false;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">В группу!</h4>
                </div>
                <div class="modal-body">
                    <div id="welcome-lesson-messages-place"></div>
                    <h3 class="pupil-info"></h3>
                    <p>Дата начала занятий: <b class="welcome-lesson-start-date"></b></p>
                    <input type="hidden" name="welcome_lesson[id]" class="welcome-lesson-id" required>
                    <b>Группа</b>
                    <div class="group-proposal"></div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="welcome_lesson[group_proposal]" value="0" onchange="WelcomeLesson.groupChange(this);"> Другая
                        </label>
                    </div>
                    <select name="welcome_lesson[group_id]" class="form-control other-group" disabled></select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Перенести</h4>
                </div>
                <div class="modal-body">
                    <div id="welcome-lesson-reschedule-messages-place"></div>
                    <h3 class="pupil-info"></h3>
                    <p>Дата занятия: <b class="welcome-lesson-date"></b></p>
                    <input type="hidden" name="welcome_lesson[id]" class="welcome-lesson-id" required>
                    <div class="form-group">
                        <label>Новая дата</label>
                        <div class="input-group date datepicker">
                            <input type="text" class="form-control date-select" name="welcome_lesson[date]" required>
                            <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary">перенести</button>
                </div>
            </form>
        </div>
    </div>
</div>
