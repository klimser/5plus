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
                htmlData += '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="' + group.price + '" onclick="Payment.selectSum(this);">' +
                    'за 1 месяц ' + group.price + ' сум' +
                    '</button></div>' +

                    '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="' + group.priceDiscount + '" onclick="Payment.selectSum(this);">' +
                    'за 4 месяца ' + group.priceDiscount + ' сум' +
                    '</button></div>' +

                    '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="none" onclick="Payment.selectSum(this);">другая сумма</button></div>' +
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
        if (sum === 'none') {
            $(amountInput).val(0).prop('disabled', false);
        } else {
            $(amountInput).val(parseInt(sum)).prop('disabled', true);
        }

        $("#pupil").data("val", this.user).val(this.users[this.user].name);
        $("#group").data("val", $(e).closest(".group-payments").data("groupid")).val($(e).closest(".group-payments").data("groupname"));
        $("#payment_form").modal();
    },
    lockPayButton: function() {
        $(".pay_button").prop("disabled", true);
        Main.throwFlashMessage('#message_board', "Подготовка к оплате. Пожалуйста, подождите...", 'alert-info');
    },
    unlockPayButton: function() {
        $(".pay_button").prop("disabled", false);
    },
    completePayment: function(form) {
        if (!$("#pupil").data("val") || !$("#group").data("val") || $("#amount").val() < 1000) return false;

        this.lockPayButton();
        $.ajax({
            url: $(form).attr('action'),
            type: 'post',
            dataType: 'json',
            data: {
                pupil: $("#pupil").data("val"),
                group: $("#group").data("val"),
                amount: $("#amount").val()
            },
            success: function(data) {
                if (data.status === 'error') {
                    Main.throwFlashMessage('#message_board', "Ошибка: " + data.message, 'alert-danger');
                    Payment.unlockPayButton();
                } else {
                    location.assign(data.payment_url + '/invoice/get?storeId=' + data.store_id + '&transactionId=' + data.payment_id + '&redirectLink=' + data.redirect_link);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#message_board', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                Payment.unlockPayButton();
            }
        });

        return false;
    },
    completeNewPayment: function(form) {
        this.lockPayButton();
        $.ajax({
            url: $(form).attr('action'),
            type: 'post',
            dataType: 'json',
            data: $(form).serialize(),
            success: function(data) {
                if (data.status === 'error') {
                    Main.throwFlashMessage('#message_board', "Ошибка: " + data.message, 'alert-danger');
                    Payment.unlockPayButton();
                } else {
                    location.assign(data.payment_url + '/invoice/get?storeId=' + data.store_id + '&transactionId=' + data.payment_id + '&redirectLink=' + data.redirect_link);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#message_board', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                Payment.unlockPayButton();
            }
        });

        return false;
    }
};
