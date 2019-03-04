let User = {
    changePersonType: function() {
        switch ($('input.person_type:checked').val()) {
            case '2':
                $("#parents_block").removeClass('hidden');
                $("#company_block").addClass('hidden');
                break;
            case '4':
                $("#parents_block").addClass('hidden');
                $("#company_block").removeClass('hidden');
                break;
        }
    },
    changeParentType: function() {
        switch ($('input[name="parent_type"]:checked').val()) {
            case 'none':
                $("#parents_select").addClass('hidden');
                $("#parents_form").addClass('hidden');
                break;
            case 'exist':
                $("#parents_select").removeClass('hidden');
                $("#parents_form").addClass('hidden');
                $('#parents_select select.chosen').chosen({
                    disable_search_threshold: 6,
                    no_results_text: 'Не найдено',
                    placeholder_text_single: 'Выберите родителей'
                });
                break;
            case 'new':
                $("#parents_select").addClass('hidden');
                $("#parents_form").removeClass('hidden');
                break;
        }
    },
    changeCompanyType: function() {
        switch ($('input[name="company_type"]:checked').val()) {
            case 'exist':
                $("#company_select").removeClass('hidden');
                $("#company_form").addClass('hidden');
                $('#company_select select.chosen').chosen({
                    disable_search_threshold: 6,
                    no_results_text: 'Не найдено',
                    placeholder_text_single: 'Выберите компанию'
                });
                break;
            case 'new':
                $("#company_select").addClass('hidden');
                $("#company_form").removeClass('hidden');
                break;
        }
    },
    setAmountBlockVisibility: function() {
        if ($("#add_payment_switch").is(":checked") || $("#add_contract_switch").is(":checked")) {
            $("#amount_block").removeClass('hidden').find("input").prop("disabled", false);
        } else {
            $("#amount_block").addClass('hidden').find("input").prop("disabled", true);
        }
    },
    checkAddGroup: function(e) {
        if (e.checked) {
            $('#add_group').removeClass("hidden")
                .find("input").prop("disabled", false);
            $("#add_payment_switch").prop("disabled", false);
            $("#contract_group_block").addClass("hidden");
            this.setAmountHelperButtons($("#group"), true);
        } else {
            $('#add_group').addClass("hidden")
                .find("input").prop("disabled", true);
            $("#contract_group_block").removeClass("hidden");
            if ($("#add_contract_switch").is(":checked")) this.setAmountHelperButtons($("#contract_group"), true);
            let paymentSwitch = $("#add_payment_switch");
            if (paymentSwitch.is(":checked")) paymentSwitch.click();
            paymentSwitch.prop("disabled", true);
        }
        this.setAmountBlockVisibility();
    },
    checkAddPayment: function(e) {
        if (e.checked) {
            $('#amount_block').removeClass('hidden');
            $('#add_payment').removeClass('hidden')
                .find("input").prop('disabled', false);
            if ($('#add_contract_switch').is(":checked")) {
                $('#add_contract_switch').click();
            }
            this.checkContractType();
        } else {
            $('#add_payment').addClass('hidden')
                .find("input").prop('disabled', true);
        }
        this.setAmountBlockVisibility();
    },
    checkContractType: function() {
        $("#contract").prop("disabled", $("input[name='payment[contractType]']:checked").val() === "auto");
    },
    checkAddContract: function(e) {
        if (e.checked) {
            $('#amount_block').removeClass('hidden');
            $('#add_contract').removeClass('hidden')
                .find('input[type!="checkbox"]').prop('disabled', false);
            if ($('#add_payment_switch').is(":checked")) {
                $('#add_payment_switch').click();
            }
        } else {
            $('#add_contract').addClass('hidden')
                .find('input[type!="checkbox"]').prop('disabled', true);
        }
        this.checkAddGroup($("#add_group_switch").get(0));
    },
    getHelperButtonHtml(amount, label) {
        return '<button type="button" class="btn btn-default btn-xs" onclick="User.setAmount(' + amount + ');">' + label + '</button>'
    },
    setAmountHelperButtons: function(select, flushAmount) {
        let opt = $(select).find("option:selected");
        if (flushAmount) $("#amount").val('');
        $("#amount_helper_buttons").html(
            this.getHelperButtonHtml($(opt).data('price'), 'за 1 месяц') +
            this.getHelperButtonHtml($(opt).data('price3'), 'за 3 месяца')
        );
    },
    setAmount: function(amount) {
        $("#amount").val(amount);
    },
    init: function() {
        if ($("#add_group_switch").is(":checked")) this.setAmountHelperButtons($("#group"));
        else if ($("#add_contract_switch").is(":checked")) this.setAmountHelperButtons($("#contract_group"));
    }
};