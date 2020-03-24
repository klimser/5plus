<div class="modal fade" tabindex="-1" role="dialog" id="modal-income">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="income-form" onsubmit="Dashboard.completeIncome(this); return false;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Принять оплату</h4>
                </div>
                <div class="modal-body">
                    <div id="income-messages-place"></div>
                    <div class="form-horizontal">
                        <input type="hidden" name="income[userId]" id="income-user-id" required>
                        <input type="hidden" name="income[groupId]" id="income-group-id" required>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Студент</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="income-pupil-name"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Группа</label>
                            <div class="col-sm-9">
                                <p class="form-control-static" id="income-group-name"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" for="income-amount">Сумма</label>
                            <div class="col-sm-9">
                                <input id="income-amount" name="income[amount]" type="number" min="1000" step="1000" class="form-control" placeholder="Сумма оплаты" autocomplete="off" required>
                                <div class="amount-helper-buttons">
                                    <button type="button" class="btn btn-default btn-xs price" onclick="Dashboard.setAmount(this);">за 1 месяц</button>
                                    <button type="button" class="btn btn-default btn-xs price3" onclick="Dashboard.setAmount(this);">за 3 месяца</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_comment" class="col-sm-3 control-label">Комментарий к платежу</label>
                            <div class="col-sm-9">
                                <input id="payment_comment" name="income[comment]" class="form-control" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                    <button type="submit" class="btn btn-primary" id="income-button">принять</button>
                </div>
            </form>
        </div>
    </div>
</div>
