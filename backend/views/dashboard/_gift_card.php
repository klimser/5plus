<div class="modal fade" tabindex="-1" role="dialog" id="modal-gift-card">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel">Принять оплату</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" id="contract-form" onsubmit="Dashboard.completeContract(this); return false;">
                    <input type="hidden" name="contractId" id="contract-id">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Студент</label>
                        <div class="col-sm-10">
                            <p class="form-control-static" id="contract-pupilName"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Группа</label>
                        <div class="col-sm-10">
                            <p class="form-control-static" id="contract-groupName"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Сумма</label>
                        <div class="col-sm-10">
                            <p class="form-control-static" id="contract-amount"></p>
                        </div>
                    </div>
                    <div id="contract-pupil-exists">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Занимается с</label>
                            <div class="col-sm-10">
                                <p class="form-control-static" id="contract-pupilDateStart"></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Оплачено до</label>
                            <div class="col-sm-10">
                                <p class="form-control-static" id="contract-pupilDateCharge"></p>
                            </div>
                        </div>
                    </div>
                    <div id="contract-pupil-new">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Начало занятий в группе</label>
                            <div class="col-sm-10">
                                <div class="input-group date datepicker">
                                    <input class="form-control" name="contractPupilDateStart" id="contract-createDate" value="" required pattern="\d{2}\.\d{2}\.\d{4}">
                                    <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                                </div>
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
