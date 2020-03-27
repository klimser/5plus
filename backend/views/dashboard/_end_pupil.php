<?php
use common\models\GroupPupil;
?>
<div class="modal fade" tabindex="-1" role="dialog" id="modal-end-pupil">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="end-pupil-form" onsubmit="Dashboard.endPupil(this); return false;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Завершение занятий в группе</h4>
                </div>
                <div class="modal-body">
                    <div id="end-pupil-messages-place"></div>
                    <div class="form-horizontal">
                        <input type="hidden" name="end-pupil[id]" id="end-pupil-id">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Студент</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="end-pupil-pupil-name"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Группа</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="end-pupil-group"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Когда перестал(а) ходить (!!! не день последнего занятия, а следующий !!!)</label>
                            <div class="col-sm-9">
                                <div class="input-group date datepicker">
                                    <input class="form-control" name="end-pupil[date]" id="end-pupil-date" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                                    <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Причина</label>
                            <div class="col-sm-9">
                                <?php foreach (GroupPupil::END_REASON_LABELS as $id => $label): ?>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="end-pupil[reasonId]" value="<?= $id; ?>" required> <?= $label; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <textarea name="end-pupil[reasonComment]" class="form-control" rows="3" placeholder="другая причина и прочая информация"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                    <button type="submit" class="btn btn-primary" id="end-pupil-button">завершить</button>
                </div>
            </form>
        </div>
    </div>
</div>
