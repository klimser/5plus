<div id="modal-age_confirmation" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Отправить СМС для подтверждения возраста</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="age-messages-place"></div>
                <input type="hidden" id="age-user-id" required>
                <div class="form-group row">
                    <label class="col-12 col-sm-3 control-label" for="age-student-name">Студент</label>
                    <div class="col-12 col-sm-9">
                        <input readonly class="form-control-plaintext" id="age-student-name">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-12 col-sm-6">
                        <button type="button" id="age-phone-1" class="btn btn-outline-primary btn-block mb-2" onclick="Dashboard.sendAgeConfirmationSms(this); return false;"></button>
                    </div>
                    <div class="col-12 col-sm-6">
                        <button type="button" id="age-phone-2" class="btn btn-outline-primary btn-block mb-2" onclick="Dashboard.sendAgeConfirmationSms(this); return false;"></button>
                    </div>
                    <div class="col-12 col-sm-6">
                        <button type="button" id="age-phone-3" class="btn btn-outline-primary btn-block mb-2" onclick="Dashboard.sendAgeConfirmationSms(this); return false;"></button>
                    </div>
                    <div class="col-12 col-sm-6">
                        <button type="button" id="age-phone-4" class="btn btn-outline-primary btn-block mb-2" onclick="Dashboard.sendAgeConfirmationSms(this); return false;"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
