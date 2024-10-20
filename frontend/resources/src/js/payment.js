let Payment = {
    users: {},
    user: null,
    selectPupil: function(e) {
        this.user = $(e).data("pupil");
        $("#user_select").find("button.pupil-button").removeClass('btn-primary').addClass('btn-outline-dark');
        $(e).addClass('btn-primary').removeClass('btn-outline-dark');
        this.renderGroupSelect();
    },
    renderGroupSelect: function() {
        if (this.user !== null && this.users.hasOwnProperty(this.user)) {
            let htmlData = '';
            this.users[this.user].groups.forEach(function(group) {
                let addonClass = 'outline-dark';
                let addonText = '';
                if (group.debt > 0) {
                    addonClass = 'danger';
                    addonText = 'задолженность ' + group.debt + ' сум';
                } else if (group.paid.length > 0) {
                    addonText = 'оплачено до ' + group.paid;
                }
                htmlData += '<button class="mb-3 btn btn-lg btn-' + addonClass + '" type="button" data-id="' + group.id + '" onclick="Payment.toggleGroup(this);">' +
                    group.name +
                    (addonText.length > 0 ? '<br><small>' + addonText + '</small>' : '') +
                    '</button><div id="payment-' + group.id + '" class="group-payments collapse" data-groupid="' + group.id + '" data-groupname="' + group.name + '"><div class="row">';
                if (group.debt > 0) {
                    htmlData += '<div class="col-12 col-md-auto mb-2"><button class="btn btn-primary btn-block" data-sum="' + group.debt + '" onclick="Payment.selectSum(this);">' +
                        'Погасить задолженность ' + group.debt + ' сум' +
                        '</button></div>';
                }
                htmlData += '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="' + group.priceLesson + '" data-limit="' + group.priceDiscountLimit + '" onclick="Payment.selectSum(this);">' +
                    'за 1 занятие ' + group.priceLesson + ' сум' +
                    '</button></div>' +

                    '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="' + group.priceMonth + '" data-limit="' + group.priceDiscountLimit + '" onclick="Payment.selectSum(this);">' +
                    'за 1 месяц ' + group.priceMonth + ' сум' +
                    '</button></div>' +

                    '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="none" data-limit="' + group.priceDiscountLimit + '" onclick="Payment.selectSum(this);">другая сумма</button></div>' +
                    '</div></div><hr>';
            });

            $("#group_select").html(htmlData);
            if (this.users[this.user].groups.length === 1) {
                $("#group_select").find('button').get(0).click();
            }
        }
    },
    toggleGroup: function(e) {
        $(".group-payments").collapse("hide");
        $("#payment-" + $(e).data("id")).collapse("show");
    },
    selectSum: function(e) {
        let amountInput = $("#amount");
        let sum = $(e).data("sum");
        $(amountInput).data('discountLimit', $(e).data("limit"));
        if (sum === 'none') {
            $(amountInput).val(0).prop('disabled', false);
        } else {
            $(amountInput).val(parseInt(sum)).prop('disabled', true);
        }

        $("#pupil").data("val", this.user).val(this.users[this.user].name);
        $("#group").data("val", $(e).closest(".group-payments").data("groupid")).val($(e).closest(".group-payments").data("groupname"));
        $("#payment_form").modal();
        Payment.checkAmount(amountInput);
    },
    lockPayButton: function() {
        $(".pay_button").prop("disabled", true);
        Main.throwFlashMessage('#message_board', "Подготовка к оплате. Пожалуйста, подождите...", 'alert-info');
    },
    unlockPayButton: function() {
        $(".pay_button").prop("disabled", false);
    },
    completePayment: function(button) {
        let form = $(button).closest("form");
        if (!$("#pupil").data("val") || !$("#group").data("val") || $("#amount").val() < 1000 || ($(form).find('#giftcard-email').length > 0 && !$("#agreement").is(":checked"))) return false;

        this.lockPayButton();
        $.ajax({
            url: $(form).attr('action'),
            type: 'post',
            dataType: 'json',
            data: {
                pupil: $("#pupil").data("val"),
                group: $("#group").data("val"),
                amount: $("#amount").val(),
                method: $(button).data("payment")
            },
        })
            .done(function(data) {
                if (data.status === 'error') {
                    Main.throwFlashMessage('#message_board', "Ошибка: " + data.message, 'alert-danger');
                } else {
                    location.assign(data.redirectUrl);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.throwFlashMessage('#message_board', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            })
            .always(Payment.unlockPayButton);

        return false;
    },
    completeNewPayment: function(button) {
        let form = $(button).closest("form");
        if (!form.get(0).reportValidity()) {
            return false;
        }
        let formData = $(form).serialize();
        this.lockPayButton();
        $.ajax({
                url: $(form).attr('action'),
                type: 'post',
                dataType: 'json',
                data: formData + "&method=" + $(button).data("payment"),
            })
            .done(function(data) {
                if (data.status === 'error') {
                    Main.throwFlashMessage('#message_board', "Ошибка: " + data.message, 'alert-danger');
                } else {
                    location.assign(data.redirectUrl);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.throwFlashMessage('#message_board', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            })
            .always(Payment.unlockPayButton);

        return false;
    },
    checkAmount: function(e) {
        let sum = parseInt($(e).val());
        if (sum > 0 && sum < $(e).data('discountLimit')) {
            $(e).parent().find("#amount-notice").collapse('show');
        } else {
            $(e).parent().find("#amount-notice").collapse('hide');
        }
    }
};
