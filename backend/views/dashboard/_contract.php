<div id="modal-contract" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="contract-form" onsubmit="Dashboard.completeContract(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Принять оплату</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="contractId" id="contract-id">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Студент</label>
                        <div class="col-sm-9">
                            <input readonly class="form-control-plaintext" id="contract-pupilName">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Группа</label>
                        <div class="col-sm-9">
                            <input readonly class="form-control-plaintext" id="contract-groupName">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Сумма</label>
                        <div class="col-sm-9">
                            <input readonly class="form-control-plaintext" id="contract-amount">
                        </div>
                    </div>
                    <div id="contract-pupil-exists" class="collapse">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Занимается с</label>
                            <div class="col-sm-9">
                                <input readonly class="form-control-plaintext" id="contract-pupilDateStart">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Оплачено до</label>
                            <div class="col-sm-9">
                                <input readonly class="form-control-plaintext" id="contract-pupilDateCharge">
                            </div>
                        </div>
                    </div>
                    <div id="contract-pupil-new" class="form-group row collapse">
                        <label class="col-sm-3 col-form-label">Начало занятий в группе</label>
                        <div class="col-sm-9">
                            <input class="form-control datepicker" name="contractPupilDateStart" id="contract-createDate" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary">принять</button>
                </div>
            </form>
        </div>
    </div>
</div>
