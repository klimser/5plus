<div class="modal fade" tabindex="-1" role="dialog" id="modal-group-move">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="group-move-form" onsubmit="Dashboard.moveGroupPupil(this); return false;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Перевести в другую группу</h4>
                </div>
                <div class="modal-body">
                    <div id="group-move-messages-place"></div>
                    <div class="form-horizontal">
                        <input type="hidden" name="group-move[id]" id="group-move-id">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Студент</label>
                            <div class="col-sm-10">
                                <p class="form-control-static" id="group-move-pupil-name"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Из группы</label>
                            <div class="col-sm-10">
                                <p class="form-control-static" id="group-move-group"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">В группу</label>
                            <div class="col-sm-10">
                                <select id="group-move-new-group" name="group-move[group_id]" class="form-control" required autocomplete="off"></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Дата перехода (первый день в новой группе)</label>
                            <div class="col-sm-10">
                                <div class="input-group date datepicker">
                                    <input class="form-control" name="group-move[date]" id="group-move-date" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                                    <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                    <button type="submit" class="btn btn-primary" id="group-move-button">перевести</button>
                </div>
            </form>
        </div>
    </div>
</div>
