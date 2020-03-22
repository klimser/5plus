let Dashboard = {
    step1: function() {
        $("#result").addClass("hidden");
        $(".step2").addClass("hidden");
        $("#step1").removeClass("hidden");
    },
    step2: function(subclass) {
        $("#step1").addClass("hidden");
        $(".step2").addClass("hidden");
        let target = $("#step2_" + subclass);
        $(target).removeClass("hidden");
        let focusable = $(target).find(".autofocus");
        if (focusable.length) {
            $(focusable).focus();
        }
    },
    find: function(form) {
        let elem = $(form).find(".search");
        let data = {
            value: $(elem).val(),
            type: $(elem).data("search")
        };
        $(".step2 button").prop("disabled", true);
        $.get("/dashboard/find", data, null, 'html')
            .done(function(content) {
                let resultContainer = $("#result");
                $(resultContainer).html(content).removeClass("hidden");
                let giftCardForm = $(resultContainer).find("#gift-card-form");
                if (giftCardForm.length > 0) {
                    Dashboard.prepareGiftCardForm(giftCardForm);
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(function() {
                $(".step2 button").prop("disabled", false);
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
                if ($(elem).prop("tagName") === 'P') {
                    $(elem).text(data[key]);
                } else {
                    $(elem).val(data[key]);
                }
            }
        });
        let pupilExistsBlock = $(form).find("#contract-pupil-exists");
        let pupilNewBlock = $(form).find("#contract-pupil-new");
        $(form).find("#contract-createDate").prop("disabled", data.groupPupil > 0);
        if (data.groupPupil > 0) {
            $(pupilExistsBlock).removeClass("hidden");
            $(pupilNewBlock).addClass("hidden");
        } else {
            $(pupilExistsBlock).addClass("hidden");
            $(pupilNewBlock).removeClass("hidden");
            let datepickerOptions = Main.datepickerDefaultSettings;
            datepickerOptions.startDate = data.groupDateStart;
            $(pupilNewBlock).find(".datepicker").datepicker(datepickerOptions);
        }
        $('#modal-contract').modal("show");
    },
    completeContract: function(form) {
        Money.completeContract(form)
            .done(function(data) {
                $("#modal-contract").modal("hide");
                if (data.status === 'ok') {
                    $("#step2_strict form").submit();
                }
            });
    },
    prepareGiftCardForm: function(form) {
        Main.initPhoneFormatted();
        $(form).find(".datepicker").datepicker(Main.datepickerDefaultSettings);
        let groupSelect = $(form).find("#new-group");
        Main.loadActiveGroups()
            .done(function(groupList) {
                let groupBlackList = [];
                $(form).find(".gift-card-existing-group").each(function(){
                    groupBlackList.push($(this).data("group"));
                });
                groupList.forEach(function(groupId) {
                    if (groupBlackList.indexOf(groupId) === -1) {
                        groupSelect.append('<option value="' + groupId + '">'
                            + Main.groupMap[groupId].name + ' (' + Main.groupMap[groupId].teacher + ')</option>');
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
        let limitDate = group.pupilLimitDate !== null && this.pupilLimitDate > group.dateStart ? this.pupilLimitDate : group.dateStart;
        $(e).closest("#gift-card-form").find(".datepicker").datepicker('setStartDate', new Date(limitDate));
    },
    completeGiftCard: function(form) {
        Money.completeGiftCard(form)
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#step2_strict form").submit();
                }
            });
    },
    toggleChildren: function(e) {
        let childrenBlock = $(e).closest(".result-parent").find(".children-list");
        if ($(childrenBlock).hasClass("hidden")) {
            $(childrenBlock).removeClass("hidden");
        } else {
            $(childrenBlock).addClass("hidden");
        }
    },
    togglePupilInfo: function(e, forceReload) {
        let childrenInfoBlock = $(e).closest(".result-pupil").find(".pupil-info");

        if ($(childrenInfoBlock).hasClass("hidden") || forceReload === true) {
            $(childrenInfoBlock).removeClass("hidden");
        } else {
            $(childrenInfoBlock).addClass("hidden");
        }

        if ($(childrenInfoBlock).html().length === 0 || forceReload === true) {
            $(childrenInfoBlock).html('<div class="loading-box"></div>');
            return $.ajax({
                url: '/user/view',
                type: 'get',
                dataType: 'html',
                data: {id: $(childrenInfoBlock).data("id")}
            })
                .done(function(data) {
                    User.init(true)
                        .fail(Main.jumpToTop);
                    WelcomeLesson.init()
                        .fail(Main.jumpToTop);
                    let htmlAddon = '<button class="btn btn-default pull-right" onclick="Dashboard.togglePupilInfo(this, true);"><span class="fas fa-sync"></span></button>';
                    $(childrenInfoBlock).html(htmlAddon + data);
                })
                .fail(Main.logAndFlashAjaxError)
                .fail(Main.jumpToTop);
        }
    },
    savePupil: function(form) {
        $.ajax({
            url: '/user/update-ajax',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                $('#user-view-messages-place').html('');
                if (data.status === 'ok') {
                    Dashboard.togglePupilInfo(form, true)
                        .done(function() {
                            data.infoFlash.forEach(function(message) {
                                Main.throwFlashMessage('#user-view-messages-place', message, 'alert-info', true);
                            });
                        });
                } else {
                    if (data.errors) {
                        data.errors.forEach(function (error) {
                            Main.throwFlashMessage('#user-view-messages-place', error, 'alert-danger', true);
                        });
                    } else {
                        Main.throwFlashMessage('#user-view-messages-place', data.message, 'alert-danger');
                    }
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .fail(Main.jumpToTop);
    },
    launchMoneyIncome: function(e) {
        let groupId = $(e).data('group');
        $('#income-messages-place').html('');
        let form = $("#income-form");
        $(form).find("#income-user-id").val($(e).data("user"));
        $(form).find("#income-group-id").val(groupId);
        $(form).find("#income-pupil-name").text($(e).closest(".result-pupil").find(".pupil-name").text());
        $(form).find("#income-amount").val(0);
        $(form).find("#payment_comment").val('');

        let group = Main.groupMap[groupId];
        $(form).find("#income-group-name").text(group.name);
        let amountHelpersBlock = $(form).find(".amount-helper-buttons");
        $(amountHelpersBlock).find(".price").data('price', group.price);
        $(amountHelpersBlock).find(".price3").data('price', group.price3);
        $("#modal-income").modal("show");
    },
    setAmount: function(e) {
        $(e).closest(".form-group").find("#income-amount").val($(e).data('price'));
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
                    Main.throwFlashMessage('#user-view-messages-place', 'Внесение денег успешно зафиксировано, номер транзакции - ' + data.paymentId, 'alert-success');
                    Main.throwFlashMessage('#user-view-messages-place', 'Договор зарегистрирован. <a target="_blank" href="' + data.contractLink + '">Распечатать</a>', 'alert-success', true);
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
    launchMovePupil: function(e) {
        let groupId = $(e).data('group');
        $('#group-move-messages-place').html('');
        let form = $("#group-move-form");

        // TODO finish it

        $(form).find("#income-user-id").val($(e).data("user"));
        $(form).find("#income-group-id").val(groupId);
        $(form).find("#income-pupil-name").text($(e).closest(".result-pupil").find(".pupil-name").text());
        $(form).find("#income-amount").val(0);
        $(form).find("#payment_comment").val('');

        let group = Main.groupMap[groupId];
        $(form).find("#income-group-name").text(group.name);
        let amountHelpersBlock = $(form).find(".amount-helper-buttons");
        $(amountHelpersBlock).find(".price").data('price', group.price);
        $(amountHelpersBlock).find(".price3").data('price', group.price3);
        $("#modal-income").modal("show");
    }
};
