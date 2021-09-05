<div id="modal-new-contract" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="new-contract-form" onsubmit="Dashboard.issueNewContract(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Выдать договор</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="new-contract-messages-place"></div>
                    <input type="hidden" name="new-contract[userId]" id="new-contract-user-id">
                    <input type="hidden" name="new-contract[groupId]" id="new-contract-group-id">
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label">Студент</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="new-contract-pupil-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label">Группа</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="new-contract-group-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label">Сумма</label>
                        <div class="col-12 col-sm-9">
                            <input class="form-control income-amount" name="new-contract[amount]" type="number" min="1000" step="1000" placeholder="Сумма оплаты" autocomplete="off" required>
                            <div class="amount-helper-buttons">
                                <button type="button" class="btn btn-outline-secondary btn-sm price-lesson" onclick="Dashboard.setAmount(this);">за 1 занятие</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm price-month" onclick="Dashboard.setAmount(this);">за 1 месяц</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary" id="new-contract-button">выдать</button>
                </div>
            </form>
        </div>
    </div>
</div>
