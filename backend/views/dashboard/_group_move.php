<div id="modal-group-move" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="group-move-form" onsubmit="Dashboard.moveGroupPupil(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Перевести в другую группу</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="group-move-messages-place"></div>
                    <input type="hidden" name="group-move[id]" id="group-move-id">
                    <div class="form-group row">
                        <label class="col-sm-3 control-label">Студент</label>
                        <div class="col-sm-9">
                            <input readonly class="form-control-plaintext" id="group-move-pupil-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 control-label">Из группы</label>
                        <div class="col-sm-9">
                            <input readonly class="form-control-plaintext" id="group-move-group">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 control-label">Последний день в старой группе</label>
                        <div class="col-sm-9">
                            <input class="form-control datepicker" name="group-move[date_from]" id="group-move-date-from" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 control-label">В группу</label>
                        <div class="col-sm-9">
                            <select id="group-move-new-group" name="group-move[group_id]" class="form-control" required autocomplete="off" onchange="GroupMove.setGroupToDateInterval(this);"></select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 control-label">Первый день в новой группе</label>
                        <div class="col-sm-9">
                            <input class="form-control datepicker" name="group-move[date_to]" id="group-move-date-to" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary" id="group-move-button">перевести</button>
                </div>
            </form>
        </div>
    </div>
</div>
