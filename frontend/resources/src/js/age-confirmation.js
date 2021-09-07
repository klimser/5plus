let AgeConfirmation = {
    timer: null,
    submitLock: false,
    agreeClick: function (e) {
        $("#confirmation-block").collapse($(e).is(':checked') ? 'show' : 'hide');
    },
    showSendSmsButton: function() {
        $("#send-sms-button-block").collapse('show');
    },
    triggerSms: function (e) {
        let form = $(e).closest('form');
        let phoneInput = $('#phone');
        if ('manual' === $(phoneInput).data('type')) {
            if (!$(phoneInput).get(0).reportValidity() || $(phoneInput).val().length < 11) {
                $(phoneInput).focus();
                return;
            }
        }
        let gToken = grecaptcha.getResponse();
        if (gToken.length === 0) {
            Main.throwFlashMessage('#messages_place', 'Подтвердите, что вы не робот', 'alert-danger');
            return false;
        }

        this.lockSendSmsButton();
        $.ajax({
            url: '/payment/send-age-confirmation-sms',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function (data) {
                if (data.timeout) {
                    AgeConfirmation.timer = data.timeout;

                    let sendSmsButton = $("#send-sms");
                    if ($(sendSmsButton).find("#timer").length === 0) {
                        $(sendSmsButton).append('<span id="timer"></span>');
                    } else {
                        $(sendSmsButton).find("#timer").html('');
                    }
                    $(sendSmsButton).find("#timer").html(' (' + AgeConfirmation.timer + ')');
                    window.setTimeout(AgeConfirmation.decrementTimer, 1000);
                } else {
                    AgeConfirmation.unlockSendSmsButton();
                }
                if ('error' === data.status) {
                    Main.throwFlashMessage('#messages_place', "Ошибка: " + data.message, 'alert-danger');
                    grecaptcha.reset();
                } else {
                    Main.throwFlashMessage('#messages_place', data.message, 'alert-success');
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
                AgeConfirmation.unlockSendSmsButton();
                grecaptcha.reset();
            });
    },
    lockSendSmsButton: function () {
        $("#send-sms").prop("disabled", true);
        let phoneInput = $("#phone");
        if ('manual' === $(phoneInput).data('type')) {
            $(phoneInput).prop('readonly', true);
        }
    },
    unlockSendSmsButton: function () {
        $("#send-sms").prop("disabled", false);
        let phoneInput = $("#phone");
        if ('manual' === $(phoneInput).data('type')) {
            $(phoneInput).prop('readonly', false);
        }
    },
    decrementTimer: function () {
        AgeConfirmation.timer--;
        let sendSmsButton = $("#send-sms");
        if (AgeConfirmation.timer === 0) {
            AgeConfirmation.unlockSendSmsButton();
            $(sendSmsButton).find("#timer").remove();
        } else {
            $(sendSmsButton).find("#timer").html(' (' + AgeConfirmation.timer + ')');
            window.setTimeout(AgeConfirmation.decrementTimer, 1000);
        }
    },
    submit: function(form) {
        if (this.submitLock) {
            return false;
        }
        let gToken = grecaptcha.getResponse();
        if (gToken.length === 0) {
            Main.throwFlashMessage('#messages_place', 'Подтвердите, что вы не робот', 'alert-danger');
            return false;
        }
        
        this.submitLock = true;
        this.lockSubmitButton();
        $.ajax({
            url: '/payment/age-confirm',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function (data) {
                if ('error' === data.status) {
                    Main.throwFlashMessage('#messages_place', "Ошибка: " + data.message, 'alert-danger');
                    grecaptcha.reset();
                    AgeConfirmation.submitLock = false;
                    AgeConfirmation.unlockSubmitButton();
                } else {
                    Main.throwFlashMessage('#messages_place', data.message, 'alert-success');
                    $("#age-confirmation-form").collapse('hide');
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
                grecaptcha.reset();
                AgeConfirmation.submitLock = false;
                AgeConfirmation.unlockSubmitButton();
            });
        return false;
    },
    lockSubmitButton: function () {
        $("#submit-button").prop("disabled", true);
    },
    unlockSubmitButton: function () {
        $("#submit-button").prop("disabled", false);
    },
    flushPhoneNumber: function(input) {
        $.ajax({
            url: '/payment/flush-age-confirmation-session',
            type: 'get',
            dataType: 'json',
        })
            .fail(function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            });
        let phoneInput = $("#phone");
        $(phoneInput).removeClass('form-control-plaintext');
        $(phoneInput).prop('readonly', false);
        $(phoneInput).data('type', 'manual');
        $(input).closest('.input-group-append').remove();
    }
};
