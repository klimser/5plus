<div id="modal-course-move" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="course-move-form" onsubmit="Dashboard.moveCourseStudent(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Перевести в другую группу</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="course-move-messages-place"></div>
                    <input type="hidden" name="course-move[id]" id="course-move-id">
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="course-move-pupil-name">Студент</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="course-move-pupil-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="course-move-course">Из группы</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="course-move-course">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="course-move-date-from">Последний день в старой группе</label>
                        <div class="col-12 col-sm-9">
                            <input class="form-control datepicker" name="course-move[date_from]" id="course-move-date-from" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="course-move-new-course">В группу</label>
                        <div class="col-12 col-sm-9">
                            <select id="course-move-new-course" name="course-move[course_id]" class="form-control" required autocomplete="off" onchange="CourseMove.setCourseToDateInterval(this);"></select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="course-move-date-to">Первый день в новой группе</label>
                        <div class="col-12 col-sm-9">
                            <input class="form-control datepicker" name="course-move[date_to]" id="course-move-date-to" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary" id="course-move-button">перевести</button>
                </div>
            </form>
        </div>
    </div>
</div>
