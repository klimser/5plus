let Money = {
    completeContract: function(form) {
        this.lockContractButton();
        return $.ajax({
            url: '/money/process-contract',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    Main.throwFlashMessage('#messages_place', 'Внесение денег успешно зафиксировано, номер транзакции - ' + data.paymentId, 'alert-success');
                    let contractResultBlock = $("#contract_result_block");
                    if (contractResultBlock.length > 0) {
                        $(contractResultBlock).html('');
                    }
                } else {
                    Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(Money.unlockContractButton)
            .always(Main.jumpToTop);
    },
    lockContractButton: function() {
        $("#contract_button").prop('disabled', true);
    },
    unlockContractButton: function() {
        $("#contract_button").prop('disabled', false);
    },
    completeGiftCard: function(form) {
        this.lockGiftButton();
        return $.ajax({
                url: '/money/process-gift-card',
                type: 'post',
                dataType: 'json',
                data: $(form).serialize()
            })
            .done(function(data) {
                if (data.status !== 'ok') {
                    Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                } else {
                    Main.throwFlashMessage('#messages_place', 'Внесение денег успешно зафиксировано, номер транзакции - ' + data.paymentId, 'alert-success');
                    Main.throwFlashMessage('#messages_place', 'Договор зарегистрирован. <a target="_blank" href="' + data.contractLink + '">Распечатать</a>', 'alert-success', true);
                    $("#gift_card_result_block").collapse("hide");
                    let giftCardInputBlock = $("#search_gift_card");
                    if (giftCardInputBlock.length > 0) {
                        $(giftCardInputBlock).val('');
                    }
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(Money.unlockGiftButton)
            .always(Main.jumpToTop);
    },
    lockGiftButton: function() {
        $("#gift-button").prop('disabled', true);
    },
    unlockGiftButton: function() {
        $("#gift-button").prop('disabled', false);
    },
};
