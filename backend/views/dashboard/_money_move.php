<div id="modal-money-move" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="money-move-form" onsubmit="Dashboard.moveMoney(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Перенести деньги в другую группу</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="money-move-messages-place"></div>
                    <input type="hidden" name="money-move[id]" id="money-move-id">
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="money-move-pupil-name">Студент</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="money-move-pupil-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="money-move-group">Из группы</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="money-move-group">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="money-move-amount">Сумма</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="money-move-amount">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="money-move-new-group">В группу</label>
                        <div class="col-12 col-sm-9">
                            <select id="money-move-new-group" name="money-move[groupId]" class="form-control" required autocomplete="off"></select>
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
