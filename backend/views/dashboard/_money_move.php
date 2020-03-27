<div class="modal fade" tabindex="-1" role="dialog" id="modal-money-move">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="money-move-form" onsubmit="Dashboard.moveMoney(this); return false;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Перенести деньги в другую группу</h4>
                </div>
                <div class="modal-body">
                    <div id="money-move-messages-place"></div>
                    <div class="form-horizontal">
                        <input type="hidden" name="money-move[id]" id="money-move-id">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Студент</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="money-move-pupil-name"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Из группы</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="money-move-group"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Сумма</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="money-move-amount"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">В группу</label>
                            <div class="col-sm-9">
                                <select id="money-move-new-group" name="money-move[groupId]" class="form-control" required autocomplete="off"></select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                    <button type="submit" class="btn btn-primary" id="money-move-button">перевести</button>
                </div>
            </form>
        </div>
    </div>
</div>
