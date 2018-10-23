var User = {
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
    checkAddGroup: function(e) {
        if (e.checked) {
            $('#add_group').removeClass("hidden");
            $('#add_group input').prop("disabled", false);
            $("#add_payment_switch").prop("disabled", false);
            $("#contract_group_block").addClass("hidden");
        } else {
            $('#add_group').addClass("hidden");
            $('#add_group input').prop("disabled", true);
            $("#contract_group_block").removeClass("hidden");
            if ($("#add_payment_switch").is(":checked")) $("#add_payment_switch").click();
            $("#add_payment_switch").prop("disabled", true);
        }
    },
    checkAddPayment: function(e) {
        if (e.checked) {
            $('#add_payment').removeClass('hidden');
            $('#add_payment input').prop('disabled', false);
            if ($('#add_contract_switch').is(":checked")) {
                $('#add_contract_switch').click();
            }
        } else {
            $('#add_payment').addClass('hidden');
            $('#add_payment input').prop('disabled', true);
        }
    },
    checkAddContract: function(e) {
        if (e.checked) {
            $('#add_contract').removeClass('hidden');
            $('#add_contract input[type!="checkbox"]').prop('disabled', false);
            if ($('#add_payment_switch').is(":checked")) {
                $('#add_payment_switch').click();
            }
        } else {
            $('#add_contract').addClass('hidden');
            $('#add_contract input[type!="checkbox"]').prop('disabled', true);
        }
    },
    checkDiscountContract: function(e) {
        if (e.checked && !($("#contract_amount").val())) {
            var discountSum;
            if (!$("#add_group").hasClass("hidden")) {
                discountSum = $("#group").find("option:selected").data('discount')
            } else {
                discountSum = $("#contract_group").find("option:selected").data('discount')
            }
            $("#contract_amount").val(discountSum);
        }
    }
};