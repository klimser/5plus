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
                        <label class="col-12 col-sm-3 col-form-label" for="contract-studentName">Студент</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="contract-studentName">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 col-form-label" for="contract-courseName">Группа</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="contract-courseName">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 col-form-label" for="contract-amount">Сумма</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="contract-amount">
                        </div>
                    </div>
                    <div id="contract-student-exists" class="collapse">
                        <div class="form-group row">
                            <label class="col-12 col-sm-3 col-form-label" for="contract-studentDateStart">Занимается с</label>
                            <div class="col-12 col-sm-9">
                                <input readonly class="form-control-plaintext" id="contract-studentDateStart">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-12 col-sm-3 col-form-label" for="contract-studentDateCharge">Оплачено до</label>
                            <div class="col-12 col-sm-9">
                                <input readonly class="form-control-plaintext" id="contract-studentDateCharge">
                            </div>
                        </div>
                    </div>
                    <div id="contract-student-new" class="form-group row collapse">
                        <label class="col-12 col-sm-3 col-form-label" for="contract-createDate">Начало занятий в группе</label>
                        <div class="col-12 col-sm-9">
                            <input class="form-control datepicker" name="contractStudentDateStart" id="contract-createDate" value="" required pattern="\d{2}\.\d{2}\.\d{4}" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary" id="contract_button">принять</button>
                </div>
            </form>
        </div>
    </div>
</div>
