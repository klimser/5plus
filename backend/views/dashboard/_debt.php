<div id="modal-debt" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="debt-form" onsubmit="Dashboard.completeDebt(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Добавить долг</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="debt-messages-place"></div>
                    <input type="hidden" name="debt[userId]" id="debt-user-id" required>
                    <input type="hidden" name="debt[groupId]" id="debt-group-id" required>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="debt-pupil-name">Студент</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="debt-pupil-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="debt-group-name">Группа</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="debt-group-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="debt-amount">Сумма долга</label>
                        <div class="col-12 col-sm-9">
                            <input class="form-control" id="debt-amount" name="debt[amount]" type="number" min="1" placeholder="Сумма оплаты" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="debt_comment" class="col-12 col-sm-3 control-label">Комментарий к платежу</label>
                        <div class="col-12 col-sm-9">
                            <input id="debt_comment" name="debt[comment]" class="form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary" id="debt-button">добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>
