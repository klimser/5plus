let Money = {
    className: 'Money',
    pupils: null,
    groups: null,
    pupilId: null,
    groupId: null,
    paymentType: null,
    findContract: function () {
        $('#messages_place').html('');
        $.ajax({
            url: '/contract/find',
            type: 'post',
            data: {
                number: $("#search_contract").val()
            },
            dataType: 'json',
            success: function (data) {
                if (data.status !== 'ok') {
                    Main.throwFlashMessage('#contract_result_block', data.message, 'alert-warning');
                } else {
                    let contractForm = '<form id="contract_form" onsubmit="return Money.completeContract(this);">' +
                        '<input type="hidden" name="id" value="' + data.id + '">' +
                        '<table class="table">' +
                        '<tr><td><b>Студент</b></td><td>' + data.user_name + '</td></tr>' +
                        '<tr><td><b>Группа</b></td><td>' + data.group_name + '</td></tr>'+
                        '<tr><td><b>Сумма</b></td><td><span class="big-font">' + data.amount + '</span>'
                        + (data.discount ? ' <span class="label label-success">со скидкой</span>' : '') + '</td></tr>' +
                        '<tr><td><b>Дата договора</b></td><td>' + data.create_date + '</td></tr>' +
                        '<tr><td><b>Компания</b></td><td>' + data.company_name + '</td></tr>';
                    if (data.group_pupil_id > 0) {
                        contractForm += '<tr><td><b>Занимается с </b></td><td><span class="big-font">' + data.date_start + '</span></td></tr>'
                            + '<tr><td><b>Оплачено до </b></td><td><span class="big-font">' + data.date_charge_till + '</span></td></tr>';
                    } else {
                        contractForm += '<tr><td><b>Начало занятий в группе</b></td><td>' +
                            '<div class="input-group date">' +
                            '<input class="form-control" name="pupil_start_date" value="' + data.create_date + '" required pattern="\\d{2}\\.\\d{2}\\.\\d{4}">' +
                            '<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>' +
                            '</div>' +
                            '</td></tr>';
                    }
                    contractForm += '</table>' +
                        '<div class="form-group"><button class="btn btn-success btn-lg" id="contract_button">внести</button></div>';
                    $('#contract_result_block').html(contractForm);
                    $('#contract_form').find(".date").datepicker({
                        "autoclose": true,
                        "format": "dd.mm.yyyy",
                        "language": "ru",
                        "weekStart": 1
                    });
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            }
        });
    },
    findPupils: function () {
        this.pupilId = null;
        this.groupId = null;
        this.paymentType = null;
        $('#messages_place').html('');
        $("#income_form").addClass("hidden");
        $("#payment_type_block").addClass("hidden");
        $('.phone-search-result').html('');
        $.ajax({
            url: '/user/find-by-phone',
            type: 'post',
            data: {
                phone: $("#search_phone").val()
            },
            dataType: 'json',
            success: function (data) {
                if (data.pupils !== undefined && data.pupils.length > 0) {
                    let pupilList = '';
                    Money.pupils = {};
                    Money.groups = {};
                    data.pupils.forEach(function(pupil) {
                        pupilList += '<button class="btn btn-default btn-lg margin-right-10" type="button" id="pupil-' + pupil.id + '" onclick="' + Money.className + '.setPupil(' + pupil.id + ');">'
                            + pupil.name
                            + '</button>';
                        let newPupil = {name: pupil.name, groups: []};
                        pupil.groups.forEach(function(group) {
                            Money.groups[group.id] = group;
                            newPupil.groups.push(group.id);
                        });
                        Money.pupils[pupil.id] = newPupil;
                    });

                    $('#pupils_block').html('<div class="panel panel-default"><div class="panel-body">' + pupilList + '</div></div>');
                    if (data.pupils.length === 1) $("#pupils_block").find('button:first').click();
                } else {
                    Main.throwFlashMessage('#pupils_block', '<div class="panel panel-default"><div class="panel-body">Не найдено студентов<br><a href="/user/create-pupil" target="_blank" class="btn btn-success btn-lg">Добавить <span class="glyphicon glyphicon-new-window"></span></a></div></div>', 'alert-warning');
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            }
        });
    },
    setPupil: function (pupilId) {
        this.pupilId = pupilId;
        $("#pupils_block").find("button").removeClass("btn-primary");
        $("#pupil-" + pupilId).addClass('btn-primary');
        this.renderGroupsBlock();
    },
    renderGroupsBlock: function () {
        let pupil = this.pupils[this.pupilId];
        let blockHtml = '<div class="panel panel-default"><div class="panel-body">';
        pupil.groups.forEach(function(group) {
            blockHtml += '<button class="btn btn-default btn-lg margin-right-10" type="button" id="group-' + group + '" onclick="Money.setGroup(' + group + ');">' + Money.groups[group].name + '</button>';
        });
        blockHtml += '<a href="/user/add-to-group?userId=' + this.pupilId + '" target="_blank" class="btn btn-default btn-lg">Добавить в новую группу <span class="glyphicon glyphicon-new-window"></span></a>';
        blockHtml += '</div></div>';
        $("#groups_block").html(blockHtml);
        if (pupil.groups.length === 1) $("#groups_block").find('button:first').click();
    },
    setGroup: function (groupId) {
        this.groupId = groupId;
        $("#groups_block").find("button").removeClass("btn-primary");
        $("#group-" + groupId).addClass('btn-primary');

        let group = this.groups[this.groupId];
        $("#payment-0").find(".price").text(group.month_price);
        $("#payment-1").find(".price").text(group.discount_price);
        $("#date_start").text(group.date_start);
        $("#date_charge_till").text(group.date_charge_till);
        $("#payment_type_block").removeClass("hidden").find("button").removeClass("btn-primary");
    },
    setPayment: function(paymentType) {
        $("#payment_type_block").find("button").removeClass("btn-primary");
        $("#payment-" + paymentType).addClass('btn-primary');
        this.paymentType = paymentType;

        let amountInput = $("#amount");
        if (this.paymentType === 1) {
            $(amountInput).val(this.groups[this.groupId].discount_price);
        } else {
            $(amountInput).val(this.groups[this.groupId].month_price);
        }
        $("#income_form").removeClass('hidden');
    },
    completeIncome: function(form) {
        if (!this.pupilId || !this.groupId || this.paymentType === null) return false;
        this.lockIncomeButton();
        $.ajax({
            url: '/money/process-income',
            type: 'post',
            dataType: 'json',
            data: {
                user: this.pupilId,
                group: this.groupId,
                amount: parseInt($(form).find("#amount").val()),
                comment: $('#payment_comment').val(),
                contract: $("#payment_type_auto").is(":checked") ? "auto" : $("#contract").val(),
                company: $(form).find("input[name=company_id]:checked").val()
            },
            success: function(data) {
                if (data.status === 'ok') {
                    Main.throwFlashMessage('#messages_place', 'Внесение денег успешно зафиксировано, номер транзакции - ' + data.paymentId, 'alert-success');
                    Main.throwFlashMessage('#messages_place', 'Договор зарегистрирован. <a target="_blank" href="' + data.contractLink + '">Распечатать</a>', 'alert-success', true);
                    $('#amount').val('');
                    $('#payment_comment').val('');
                    $("#payment_contract").val('');
                    $("#payment_date").val('');
                    $("#income_form").addClass("hidden");
                    $("#payment_type_block").addClass("hidden");
                    $('.phone-search-result').html('');
                    Money.pupilId = null;
                    Money.groupId = null;
                    Money.paymentType = null;
                }
                else Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                Money.unlockIncomeButton();
            },
            error: function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                Money.unlockIncomeButton();
            }
        });
        return false;
    },
    lockIncomeButton: function() {
        $("#income_button").prop('disabled', true);
    },
    unlockIncomeButton: function() {
        $("#income_button").prop('disabled', false);
    },
    completeContract: function(form) {
        this.lockContractButton();
        $.ajax({
            url: '/money/process-contract',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize(),
            success: function(data) {
                if (data.status === 'ok') {
                    Main.throwFlashMessage('#messages_place', 'Внесение денег успешно зафиксировано, номер транзакции - ' + data.paymentId, 'alert-success');
                    $("#contract_result_block").html('');
                }
                else Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                Money.unlockContractButton();
            },
            error: function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                Money.unlockContractButton();
            }
        });
        return false;
    },
    lockContractButton: function() {
        $("#contract_button").prop('disabled', true);
    },
    unlockContractButton: function() {
        $("#contract_button").prop('disabled', false);
    }
};