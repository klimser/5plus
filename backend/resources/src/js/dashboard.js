let Dashboard = {
    find: function(form) {
        let elem = $(form).find(".search");
        $("#search-form button").prop("disabled", true);
        $.get("/dashboard/find", {value: $(elem).val()}, null, 'html')
            .done(function(content) {
                let resultContainer = $("#result");
                $(resultContainer).html(content);
                let giftCardForm = $(resultContainer).find("#gift-card-form");
                if (giftCardForm.length > 0) {
                    Dashboard.prepareGiftCardForm(giftCardForm);
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(function() {
                $("#search-form button").prop("disabled", false);
            });
    },
    clearInput: function(e) {
        $(e).closest(".input-group").find(".search").val("").focus();
    },
    showContractForm: function(e) {
        let form = $("#contract-form");
        let data = $(e).data();
        Object.getOwnPropertyNames(data).forEach(function (key) {
            let elem = $(form).find("#contract-" + key);
            if (elem.length > 0) {
                $(elem).val(data[key]);
            }
        });
        let pupilExistsBlock = $(form).find("#contract-pupil-exists");
        let pupilNewBlock = $(form).find("#contract-pupil-new");
        $(form).find("#contract-createDate").prop("disabled", data.groupPupil > 0);
        if (data.groupPupil > 0) {
            $(pupilExistsBlock).collapse('show');
            $(pupilNewBlock).collapse("hide");
        } else {
            $(pupilExistsBlock).collapse("hide");
            $(pupilNewBlock).collapse("show");
            let datepickerOptions = Main.datepickerDefaultSettings;
            datepickerOptions.minDate = data.groupDateStart;
            $(pupilNewBlock).find(".datepicker").datepicker(datepickerOptions);
        }
        $('#modal-contract').modal("show");
    },
    completeContract: function(form) {
        Money.completeContract(form)
            .done(function(data) {
                $("#modal-contract").modal("hide");
                if (data.status === 'ok') {
                    $("#search-form").submit();
                }
            });
    },
    prepareGiftCardForm: function(form) {
        Main.initPhoneFormatted();
        $(form).find(".datepicker").datepicker(Main.datepickerDefaultSettings);
        let groupSelect = $(form).find("#new-group");
        Main.loadActiveGroups()
            .done(function(groupIds) {
                let groupBlackList = [];
                $(form).find(".gift-card-existing-group").each(function(){
                    groupBlackList.push($(this).data("group"));
                });
                $(groupSelect).html('');
                groupIds.forEach(function(groupId) {
                    if (groupBlackList.indexOf(groupId) === -1) {
                        groupSelect.append('<option value="' + groupId + '">' + Main.groupMap[groupId].name + ' (' + Main.groupMap[groupId].teacher + ')</option>');
                    }
                });
                $(groupSelect).change();
            })
            .fail(Main.logAndFlashAjaxError);
    },
    setGiftGroup: function(e) {
        $(".gift-card-existing-group").removeClass("btn-primary");
        let existingElem = $("#existing_group_id");
        let newGroupElem = $("#new-group");
        let newGroupDateElem = $("#new-group-date");
        if (parseInt(existingElem.val()) === $(e).data("group")) {
            existingElem.val('');
            $(newGroupElem).prop("disabled", false);
            $(newGroupDateElem).prop("disabled", false);
        } else {
            existingElem.val($(e).data("group"));
            $(e).addClass("btn-primary");
            $(newGroupElem).prop("disabled", true);
            $(newGroupDateElem).prop("disabled", true);
        }
    },
    selectGiftGroup: function(e) {
        let group = Main.groupMap[$(e).val()];
        let limitDate = this.pupilLimitDate !== null && this.pupilLimitDate > group.dateStart ? this.pupilLimitDate : group.dateStart;
        $(e).closest("#gift-card-form").find(".datepicker").datepicker("option", "minDate", new Date(limitDate));
    },
    completeGiftCard: function(form) {
        Money.completeGiftCard(form)
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#search-form").submit();
                }
            });
    },
    toggleChildren: function(e) {
        let childrenBlock = $(e).closest(".result-parent").find(".children-list");
        $(childrenBlock).collapse("toggle");
    },
    refreshPupilInfo: function(e) {
        let tabId = $(e).closest(".pupil-info").find(".user-view-tabs .tab-pane.active").attr("id");
        tabId = tabId.split("-");
        return this.togglePupilInfo(e, true, tabId[0]);
    },
    togglePupilInfo: function(e, forceReload, activeTab) {
        let childrenInfoBlock = $(e).closest(".result-pupil").find(".pupil-info");
        $(childrenInfoBlock).collapse(forceReload ? 'show' : 'toggle');

        if ($(childrenInfoBlock).html().length === 0 || forceReload === true) {
            $(childrenInfoBlock).html('<div class="loading-box"></div>');
            return $.ajax({
                url: '/user/view',
                type: 'get',
                dataType: 'html',
                data: {id: $(childrenInfoBlock).data("id"), tab: activeTab}
            })
                .done(function(data) {
                    let htmlAddon = '<button type="button" class="btn btn-outline-secondary float-right" onclick="Dashboard.refreshPupilInfo(this);"><span class="fas fa-sync"></span></button>';
                    $(childrenInfoBlock).html(htmlAddon + data);
                    Main.initPhoneFormatted();
                    User.init(true)
                        .fail(Main.jumpToTop);
                    WelcomeLesson.init()
                        .fail(Main.jumpToTop);
                })
                .fail(Main.logAndFlashAjaxError)
                .fail(Main.jumpToTop);
        }
    },
    showEditForm: function(prefix, e) {
        let container = $(e).closest("." + prefix + "-info-block");
        $(container).find("." + prefix + "-view-block").collapse("hide");
        $(container).find("." + prefix + "-edit-block").collapse("show")
            .find("input, textarea").prop("disabled", false);
    },
    changeParentType: function(e, inputName) {
        let block = $(e).closest(".parent-edit-block");
        $(block).find(".parent-edit-option").each(function() {
            $(this).collapse("hide");
            $(this).find("input").prop("disabled", true);
        });
        if (inputName === undefined) {
            inputName = 'parent_type';
        }
        let checkedVal = $(block).find('input[name="' + inputName + '"]:checked').val();
        let activeBlock = $(block).find(".parent-edit-" + checkedVal);
        if (activeBlock.length > 0) {
            $(activeBlock).collapse("show");
            $(activeBlock).find("input").prop("disabled", false);
        }
    },
    savePupil: function(form) {
        this.lockPupilInfoButtons(form);
        let messagesPlace = $(form).find(".user-view-messages-place");
        $.ajax({
            url: '/user/update-ajax',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    let container = $(form).closest('.pupil-info');
                    Dashboard.togglePupilInfo(form, true)
                        .done(function() {
                            let messagesPlace = $(container).find(".user-view-messages-place");
                            data.infoFlash.forEach(function(message) {
                                Main.throwFlashMessage(messagesPlace, message, 'alert-info', true);
                            });
                        });
                } else {
                    if (data.errors) {
                        data.errors.forEach(function (error) {
                            Main.throwFlashMessage(messagesPlace, error, 'alert-danger', true);
                        });
                    } else {
                        Main.throwFlashMessage(messagesPlace, data.message, 'alert-danger');
                    }
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, messagesPlace);
            })
            .always(function() {
                Dashboard.unlockPupilInfoButtons(form);
            });
    },
    lockPupilInfoButtons: function(container)
    {
        $(container).find("button").prop("disabled", true);
    },
    unlockPupilInfoButtons: function(container)
    {
        $(container).find("button").prop("disabled", false);
    },
    showMoneyIncomeForm: function(e) {
        let groupId = $(e).data('group');
        let group = Main.groupMap[groupId];
        
        $('#income-messages-place').html('');
        let form = $("#income-form");
        $(form).find("#income-user-id").val($(e).data("user"));
        $(form).find("#income-group-id").val(groupId);
        $(form).find("#income-pupil-name").val($(e).closest(".result-pupil").find(".pupil-name").text());
        $(form).find(".income-amount").val(0);
        $(form).find("#payment_comment").val('');
        $(form).find("#income-group-name").val(group.name);
        let amountHelpersBlock = $(form).find(".amount-helper-buttons");
        $(amountHelpersBlock).find(".price").data('price', group.price);
        $(amountHelpersBlock).find(".price3").data('price', group.price3);
        $("#modal-income").modal("show");
    },
    setAmount: function(e) {
        $(e).closest(".form-group").find(".income-amount").val($(e).data('price'));
    },
    completeIncome: function(form) {
        this.lockIncomeButton();
        return $.ajax({
                url: '/money/process-income',
                type: 'post',
                dataType: 'json',
                data: $(form).serialize()
            })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-income").modal("hide");
                    let container = $('#user-view-' + data.userId).closest(".pupil-info");
                    Dashboard.togglePupilInfo(container, true, 'group')
                        .done(function() {
                            let messagesPlace = $(container).find('.user-view-messages-place');
                            Main.throwFlashMessage(messagesPlace, 'Внесение денег успешно зафиксировано, номер транзакции - ' + data.paymentId, 'alert-success');
                            Main.throwFlashMessage(messagesPlace, 'Договор зарегистрирован. <a target="_blank" href="' + data.contractLink + '">Распечатать</a>', 'alert-info', true);
                        });
                } else {
                    Main.throwFlashMessage('#income-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#income-messages-place');
            })
            .always(Dashboard.unlockIncomeButton);
    },
    lockIncomeButton: function() {
        $("#income-button").prop('disabled', true);
    },
    unlockIncomeButton: function() {
        $("#income-button").prop('disabled', false);
    },
    showMoneyDebtForm: function(e) {
        let groupId = $(e).data('group');
        let group = Main.groupMap[groupId];

        $('#debt-messages-place').html('');
        let form = $("#debt-form");
        $(form).find("#debt-user-id").val($(e).data("user"));
        $(form).find("#debt-group-id").val(groupId);
        $(form).find("#debt-pupil-name").val($(e).closest(".result-pupil").find(".pupil-name").text());
        $(form).find("#debt-amount").val(0);
        $(form).find("#debt_comment").val('');
        $(form).find("#debt-group-name").val(group.name);
        $("#modal-debt").modal("show");
    },
    completeDebt: function(form) {
        this.lockDebtButton();
        return $.ajax({
            url: '/money/process-debt',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-debt").modal("hide");
                    let container = $('#user-view-' + data.userId).closest(".pupil-info");
                    Dashboard.togglePupilInfo(container, true, 'group')
                        .done(function() {
                            let messagesPlace = $(container).find('.user-view-messages-place');
                            Main.throwFlashMessage(messagesPlace, 'Долг добавлен', 'alert-success');
                        });
                } else {
                    Main.throwFlashMessage('#debt-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#debt-messages-place');
            })
            .always(Dashboard.unlockDebtButton);
    },
    lockDebtButton: function() {
        $("#debt-button").prop('disabled', true);
    },
    unlockDebtButton: function() {
        $("#debt-button").prop('disabled', false);
    },
    showNewContractForm: function(e) {
        let groupId = $(e).data('group');
        let group = Main.groupMap[groupId];
        
        $('#new-contract-messages-place').html('');
        let form = $("#new-contract-form");
        $(form).find("#new-contract-user-id").val($(e).data("user"));
        $(form).find("#new-contract-group-id").val(groupId);
        $(form).find("#new-contract-pupil-name").val($(e).closest(".result-pupil").find(".pupil-name").text());
        $(form).find(".income-amount").val(0);
        $(form).find("#new-contract-group-name").val(group.name);
        let amountHelpersBlock = $(form).find(".amount-helper-buttons");
        $(amountHelpersBlock).find(".price").data('price', group.price);
        $(amountHelpersBlock).find(".price3").data('price', group.price3);
        $("#modal-new-contract").modal("show");
    },
    issueNewContract: function(form) {
        this.lockNewContractButton();
        return $.ajax({
            url: '/contract/create-ajax',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-new-contract").modal("hide");
                    let messagesPlace = $('#user-view-' + data.userId).find('.user-view-messages-place');
                    Main.throwFlashMessage(messagesPlace, 'Договор зарегистрирован. <a target="_blank" href="' + data.contractLink + '">Распечатать</a>', 'alert-info');
                } else {
                    Main.throwFlashMessage('#new-contract-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#new-contract-messages-place');
            })
            .always(Dashboard.unlockNewContractButton);
    },
    lockNewContractButton: function() {
        $("#new-contract-button").prop('disabled', true);
    },
    unlockNewContractButton: function() {
        $("#new-contract-button").prop('disabled', false);
    },
    showMovePupilForm: function(e) {
        let groupPupilId = $(e).data('id');
        let groupId = parseInt($(e).data('group'));
        let group = Main.groupMap[groupId];
        $('#group-move-messages-place').html('');
        let form = $("#group-move-form");
        $(form).find("#group-move-id").val(groupPupilId);
        $(form).find("#group-move-group").val(group.name);
        $(form).find("#group-move-pupil-name").val($(e).closest(".result-pupil").find(".pupil-name").text());
        let optionsHtml = '';
        Main.groupActiveList.forEach(function(id) {
            if (id !== groupId) {
                optionsHtml += '<option value="' + id + '">' + Main.groupMap[id].name + '</option>';
            }
        });
        $(form).find("#group-move-new-group").html(optionsHtml);
        $(form).find("#group-move-date").val('');
        let datepickerOptions = Main.datepickerDefaultSettings;
        datepickerOptions.minDate = $(e).data('date');
        $(form).find(".datepicker").datepicker(datepickerOptions);
        $("#modal-group-move").modal("show");
    },
    moveGroupPupil: function(form) {
        this.lockMovePupilButton();
        return $.ajax({
            url: '/group/process-move-pupil',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-group-move").modal("hide");
                    Dashboard.togglePupilInfo($('#user-view-' + data.userId), true, 'group');
                } else {
                    Main.throwFlashMessage('#group-move-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#group-move-messages-place');
            })
            .always(Dashboard.unlockMovePupilButton);
    },
    lockMovePupilButton: function() {
        $("#group-move-button").prop('disabled', true);
    },
    unlockMovePupilButton: function() {
        $("#group-move-button").prop('disabled', false);
    },
    showMoveMoneyForm: function(e) {
        let groupPupilId = $(e).data('id');
        let groupId = parseInt($(e).data('group'));
        let group = Main.groupMap[groupId];
        $('#money-move-messages-place').html('');
        let form = $("#money-move-form");
        $(form).find("#money-move-id").val(groupPupilId);
        $(form).find("#money-move-amount").val($(e).data("amount"));
        $(form).find("#money-move-group").val(group.name);
        $(form).find("#money-move-pupil-name").val($(e).closest(".result-pupil").find(".pupil-name").text());
        let optionsHtml = '';
        let allowedGroupIds = $(e).data('groups');
        if (typeof allowedGroupIds === 'number') {
            allowedGroupIds = [allowedGroupIds];
        } else if (allowedGroupIds.length > 0) {
            allowedGroupIds = allowedGroupIds.split(',').map(function (x) {
                return parseInt(x, 10);
            });
        }
        if (allowedGroupIds.length > 0) {
            allowedGroupIds.forEach(function (groupId) {
                optionsHtml += '<option value="' + groupId + '">' + Main.groupMap[groupId].name + '</option>';
            });
        }
        $(form).find("#money-move-new-group").html(optionsHtml);
        $("#modal-money-move").modal("show");
    },
    moveMoney: function(form) {
        this.lockMoveMoneyButton();
        return $.ajax({
            url: '/group/process-move-money',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-money-move").modal("hide");
                    Dashboard.togglePupilInfo($('#user-view-' + data.userId), true, 'group');
                } else {
                    Main.throwFlashMessage('#money-move-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#money-move-messages-place');
            })
            .always(Dashboard.unlockMoveMoneyButton);
    },
    lockMoveMoneyButton: function() {
        $("#money-move-button").prop('disabled', true);
    },
    unlockMoveMoneyButton: function() {
        $("#money-move-button").prop('disabled', false);
    },
    showEndPupilForm: function(e) {
        let groupPupilId = $(e).data('id');
        let groupId = parseInt($(e).data('group'));
        let group = Main.groupMap[groupId];
        $('#end-pupil-messages-place').html('');
        let form = $("#end-pupil-form");
        $(form).find("#end-pupil-id").val(groupPupilId);
        $(form).find("#end-pupil-group").val(group.name);
        $(form).find("#end-pupil-pupil-name").val($(e).closest(".result-pupil").find(".pupil-name").text());

        let groupLimitDate = $(e).data("date");
        let limitDate = this.pupilLimitDate !== null && this.pupilLimitDate > groupLimitDate ? this.pupilLimitDate : groupLimitDate;
        let datepickerOptions = Main.datepickerDefaultSettings;
        datepickerOptions.minDate = new Date(limitDate);
        $(form).find(".datepicker").datepicker(datepickerOptions);

        $("#modal-end-pupil").modal("show");
    },
    endPupil: function(form) {
        this.lockEndPupilButton();
        return $.ajax({
            url: '/group/end-pupil',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-end-pupil").modal("hide");
                    Dashboard.togglePupilInfo($('#user-view-' + data.userId), true, 'group');
                } else {
                    Main.throwFlashMessage('#end-pupil-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#end-pupil-messages-place');
            })
            .always(Dashboard.unlockEndPupilButton);
    },
    lockEndPupilButton: function() {
        $("#end-pupil-button").prop('disabled', true);
    },
    unlockEndPupilButton: function() {
        $("#end-pupil-button").prop('disabled', false);
    },
    filterPayments: function(e) {
        let container = $(e).closest(".view-payments");
        let filterGroup = $(container).find(".filter-group").val();
        let showExpenses = $(container).find(".filter-type").is(':checked');
        let paymentsTable = $(container).find("table.payments-table tbody");
        if (filterGroup > 0) {
            $(paymentsTable).find('tr').removeClass('show');
            $(paymentsTable).find('tr.group-' + filterGroup).addClass('show');
        } else {
            $(paymentsTable).find('tr').addClass('show');
        }
        
        if (!showExpenses) {
            $(paymentsTable).find('tr.expense').removeClass('show');
        }
    },
    filterGroups: function(e) {
        $(e).closest(".groups").find(".groups-table .group-item.inactive")
            .collapse($(e).is(':checked') ? 'show' : 'hide');
    }
};
