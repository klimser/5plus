var Money = {
    customers: null,
    groups: null,
    customerId: null,
    groupId: null,
    findCustomer: function() {
        $('#messages_place').html('');
        $.ajax({
            url: '/user/find-by-phone',
            type: 'post',
            data: {
                phone: $("#search_phone").val()
            },
            dataType: 'json',
            success: function(data) {
                if (data.pupils !== undefined && data.pupils.length > 0) {
                    var customerList = '<div id="customer-list">';
                    Money.customers = {};
                    Money.groups = {};
                    for (var i = 0; i < data.pupils.length; i++) {
                        customerList += '<button class="btn btn-default btn-lg separated-button" type="button" id="customer-' + data.pupils[i].id + '" onclick="Money.setCustomer(' + data.pupils[i].id + ');">'
                            + data.pupils[i].name
                            + '</button>';
                        var customer = {name: data.pupils[i].name, groups: []};
                        for (var k = 0; k < data.pupils[i].groups.length; k++) {
                            Money.groups[data.pupils[i].groups[k].id] = data.pupils[i].groups[k];
                            customer.groups.push(data.pupils[i].groups[k].id);
                        }
                        Money.customers[data.pupils[i].id] = customer;
                    }
                    customerList += '</div>';
                    $('#search_results_block').html('<div class="panel panel-default"><div class="panel-body">' + customerList + '<hr>' +
                        '<div id="groups_block"></div></div></div>');
                    if (data.pupils.length === 1) $("#customer-list").find('button:first').click();
                } else {
                    Main.throwFlashMessage('#search_results_block', '<div class="panel panel-default"><div class="panel-body">Не найдено учеников<br><a href="/user/create-pupil" target="_blank" class="btn btn-success btn-lg">Добавить <span class="glyphicon glyphicon-new-window"></span></a></div></div>', 'alert-warning');
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            }
        });
    },
    setCustomer: function(customerId) {
        this.customerId = customerId;
        $("#customer-list").find("button").removeClass("btn-primary");
        $("#customer-" + customerId).addClass('btn-primary');
        this.renderGroupsBlock();
    },
    renderGroupsBlock: function() {
        var customer = this.customers[this.customerId];
        var blockHtml = '<div class="row" id="group-list">' +
            '<div class="col-xs-12 col-sm-6 col-md-4"><button class="btn btn-default btn-lg full-width" type="button" id="group-0" onclick="Money.setPayment(0);">Без скидки</button></div>';
        for (var i = 0; i < customer.groups.length; i++) {
            blockHtml += '<div class="col-xs-12 col-sm-6 col-md-4"><button class="btn btn-default btn-lg full-width" type="button" id="group-' + customer.groups[i] + '" onclick="Money.setPayment(' + customer.groups[i] + ');">' + this.groups[customer.groups[i]].name + '<br><small>' + this.groups[customer.groups[i]].month_price_discount + ' в месяц</small></button></div>';
        }
        blockHtml += '</div><hr><div id="payment_block" class="hidden"><div class="row"><div class="col-xs-12">' +
            '<form id="income_form" onsubmit="return Money.completeIncome(this);">' +
                '<div class="form-group"><div class="input-group"><input id="amount" name="payment_sum" type="number" min="1000" step="1000" class="form-control input-lg" placeholder="Сумма оплаты" required><div class="input-group-addon">сум</div></div></div>' +
                '<div class="form-group"><button class="btn btn-success btn-lg" id="income_button">внести</button></div>' +
            '</form></div></div></div>';
        $("#groups_block").html(blockHtml);
        if (customer.groups.length === 0) $("#groups_block").find('button:first').click();
    },
    setPayment: function(groupId) {
        this.groupId = groupId;
        $("#group-list").find("button").removeClass("btn-primary");
        $("#group-" + groupId).addClass('btn-primary');
        this.renderPaymentBlock();
    },
    renderPaymentBlock: function() {
        var amountInput = $("#income_form").find("#amount");
        if (this.groupId > 0) {
            $(amountInput).val(this.groups[this.groupId].month_price_discount * 3);
        } else {
            if (this.customers[this.customerId].groups.length === 1) {
                $(amountInput).val(this.groups[this.customers[this.customerId].groups[0]].month_price);
            } else $(amountInput).val(0);
        }
        $("#payment_block").removeClass('hidden');
    },
    completeIncome: function(form) {
        var paymentAmount = parseInt($(form).find("#amount").val());
        if (paymentAmount === 0 || (this.groupId > 0 && paymentAmount < 0)) {
            Main.throwFlashMessage('#messages_place', "Неверная сумма", 'alert-danger');
            return false;
        }
        var paymentDate = $("#payment_date").val();
        if (!paymentDate.length) {
            Main.throwFlashMessage('#messages_place', 'Выберите дату платежа', 'alert-danger');
            return false;
        }
        this.lockIncomeButton();
        $.ajax({
            url: '/money/process-income',
            type: 'post',
            dataType: 'json',
            data: {
                payment: {
                    user: this.customerId,
                    amount: paymentAmount,
                    comment: $('#comment').val(),
                    group: this.groupId,
                    date: paymentDate,
                    contract: $("#contract").val()
                }
            },
            success: function(data) {
                if (data.status === 'ok') {
                    Main.throwFlashMessage('#messages_place', 'Внесение денег успешно зафиксировано, номер транзакции - ' + data.paymentId, 'alert-success');
                    $("#search_results_block").html('');
                    $('#comment').val('');
                    $("#contract").val('');
                    $("#payment_date").val('');
                    Money.customerId = null;
                    Money.groupId = null;
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
    init: function() {
        $("#search_phone").inputmask({"mask": "99 999-9999"});
    }
};