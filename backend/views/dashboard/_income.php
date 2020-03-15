<div class="modal fade" tabindex="-1" role="dialog" id="modal-income">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel">Принять оплату</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" id="income-form" onsubmit="Dashboard.completeIncome(this); return false;">
                    <input type="hidden" name="userId" id="income-user-id">
                    <input type="hidden" name="groupId" id="income-group-id">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Студент</label>
                        <div class="col-sm-10">
                            <p class="form-control-static" id="income-pupil-name"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Группа</label>
                        <div class="col-sm-10">
                            <p class="form-control-static" id="income-group-name"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Сумма</label>
                        <div class="col-sm-10">
                            <input id="income-amount" name="amount" type="number" min="1000" step="1000" class="form-control" placeholder="Сумма оплаты" required>
                            <div class="amount-helper-buttons">
                                <button type="button" class="btn btn-default btn-xs price" onclick="Dashboard.setAmount(this);">за 1 месяц</button>
                                <button type="button" class="btn btn-default btn-xs price3" onclick="Dashboard.setAmount(this);">за 3 месяца</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary" id="contract_button">принять</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
