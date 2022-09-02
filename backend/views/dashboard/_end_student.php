<?php
use common\models\CourseStudent;
?>
<div id="modal-end-student" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="end-student-form" onsubmit="Dashboard.endStudent(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Завершение занятий в группе</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="end-student-messages-place"></div>
                    <input type="hidden" name="end-student[id]" id="end-student-id">
                    <div class="form-group row">
                        <label class="col-12 col-sm-4 col-form-label" for="end-student-student-name">Студент</label>
                        <div class="col-12 col-sm-8">
                            <input readonly class="form-control-plaintext" id="end-student-student-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-4 col-form-label" for="end-student-course">Группа</label>
                        <div class="col-12 col-sm-8">
                            <input readonly class="form-control-plaintext" id="end-student-course">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-4 col-form-label" for="end-student-date">День последнего занятия</label>
                        <div class="col-12 col-sm-8">
                            <input class="form-control datepicker" name="end-student[date]" id="end-student-date" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-4 col-form-label">Причина</label>
                        <div class="col-12 col-sm-8">
                            <?php foreach (CourseStudent::END_REASON_LABELS as $id => $label): ?>
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="radio" name="end-student[reasonId]" value="<?= $id; ?>" required autocomplete="off">
                                        <?= $label; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <textarea name="end-student[reasonComment]" class="form-control" rows="3" placeholder="другая причина и прочая информация"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary" id="end-student-button">завершить</button>
                </div>
            </form>
        </div>
    </div>
</div>
