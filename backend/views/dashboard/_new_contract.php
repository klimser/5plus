<div class="modal fade" tabindex="-1" role="dialog" id="modal-new-contract">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="new-contract-form" onsubmit="Dashboard.issueNewContract(this); return false;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Выдать договор</h4>
                </div>
                <div class="modal-body">
                    <div id="new-contract-messages-place"></div>
                    <div class="form-horizontal">
                        <input type="hidden" name="new-contract[userId]" id="new-contract-user-id">
                        <input type="hidden" name="new-contract[groupId]" id="new-contract-group-id">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Студент</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="new-contract-pupil-name"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Группа</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="new-contract-group-name"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Сумма</label>
                            <div class="col-sm-9">
                                <input class="form-control income-amount" name="new-contract[amount]" type="number" min="1000" step="1000" placeholder="Сумма оплаты" autocomplete="off" required>
                                <div class="amount-helper-buttons">
                                    <button type="button" class="btn btn-default btn-xs price" onclick="Dashboard.setAmount(this);">за 1 месяц</button>
                                    <button type="button" class="btn btn-default btn-xs price3" onclick="Dashboard.setAmount(this);">за 3 месяца</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                    <button type="submit" class="btn btn-primary" id="new-contract-button">выдать</button>
                </div>
            </form>
        </div>
    </div>
</div>
