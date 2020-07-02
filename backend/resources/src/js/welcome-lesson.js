let WelcomeLesson = {
    statusUnknown: 1,
    statusPassed: 2,
    statusMissed: 3,
    statusCanceled: 4,
    statusDenied: 5,

    denyReasonTeacher: 1,
    denyReasonLevelTooLow: 2,
    denyReasonLevelTooHigh: 3,
    denyReasonOtherGroup: 4,
    denyReasonTooCrowded: 5,
    denyReasonSubject: 6,
    denyReasonOther: 7,

    init: function() {
        return Main.loadActiveGroups()
            .done(function() {
                WelcomeLesson.fillTableButtons("table tr.welcome-row");
            })
            .fail(Main.logAndFlashAjaxError);
    },
    
    changeStatusHandler: function(e, id, status) {
        return Main.changeEntityStatus('welcome-lesson', id, status, e)
            .done(function (data) {
                if (data.status === 'ok') {
                    WelcomeLesson.setButtons($('tr[data-key="' + data.result.id + '"] td:last-child'), data.result);
                }
            });
    },
    fillTableButtons: function(rowSelector) {
        $(rowSelector).each(function() {
            let buttonColumn = $(this).find("td.buttons-column");
            if ($(buttonColumn).html().length === 0) {
                WelcomeLesson.setButtons($(buttonColumn), {
                    id: $(this).data("key"),
                    status: $(this).data("status"),
                    denyReason: $(this).data("denyReason"),
                    date: $(this).data("date")
                });
            }
        });
    },
    setButtons: function(e, data) {
        let contents = '';
        switch (data.status) {
            case this.statusUnknown:
                contents =
                    '<button type="button" title="Проведено" class="btn btn-primary" onclick="WelcomeLesson.changeStatusHandler(this, ' + data.id + ', ' + WelcomeLesson.statusPassed + ')">' +
                        '<span class="fas fa-check"></span>' +
                    '</buttona>' +
                    '<button type="button" title="Отменено" class="btn btn-danger ml-2" onclick="WelcomeLesson.changeStatusHandler(this, ' + data.id + ', ' + WelcomeLesson.statusCanceled + ');">' +
                        '<span class="fas fa-times"></span>' +
                    '</button>' +
                    '<button type="button" title="Перенести" class="btn btn-outline-dark ml-2" onclick="WelcomeLesson.showRescheduleForm(this, ' + data.id + ', \'' + data.date + '\');">' +
                        '<span class="fas fa-history"></span>' +
                    '</button>';
                break;
            case this.statusPassed:
                contents =
                    '<button class="btn btn-primary" type="button" title="В группу!" onclick="WelcomeLesson.showMovingForm(this, ' + data.id + ')">' +
                        '<span class="fas fa-user-check"></span>' +
                    '</button>' +
                    '<button type="button" title="Не пришёл" class="btn btn-warning ml-2" onclick="WelcomeLesson.changeStatusHandler(this, ' + data.id + ', ' + WelcomeLesson.statusMissed + ')">' +
                        '<span class="fas fa-user-slash"></span>' +
                    '</buttona>' +
                    '<button type="button" title="Не будет ходить" class="btn btn-danger ml-2" onclick="WelcomeLesson.changeStatusHandler(this, ' + data.id + ', ' + WelcomeLesson.statusDenied + ')">' +
                        '<span class="fas fa-running"></span>' +
                    '</buttona>';
                break;
            case this.statusDenied:
                if (!data.denyReason) {
                    contents =
                        '<form method="post" class="deny-details-form" onsubmit="return WelcomeLesson.setDenyDetails(' + data.id + ', this);">' +
                            '<div class="form-check"><label class="form-check-label">' +
                            '<input class="form-check-input" type="radio" name="deny_reason" value="' + this.denyReasonTeacher + '"> не понравился учитель</label></div>' +
                            '<div class="form-check"><label class="form-check-label">' +
                            '<input class="form-check-input" type="radio" name="deny_reason" value="' + this.denyReasonLevelTooLow + '"> нужен уровень выше</label></div>' +
                            '<div class="form-check"><label class="form-check-label">' +
                            '<input class="form-check-input" type="radio" name="deny_reason" value="' + this.denyReasonLevelTooHigh + '"> нужен уровень ниже</label></div>' +
                            '<div class="form-check"><label class="form-check-label">' +
                            '<input class="form-check-input" type="radio" name="deny_reason" value="' + this.denyReasonOtherGroup + '"> придет в другую группу</label></div>' +
                            '<div class="form-check"><label class="form-check-label">' +
                            '<input class="form-check-input" type="radio" name="deny_reason" value="' + this.denyReasonTooCrowded + '"> слишком большая группа</label></div>' +
                            '<div class="form-check"><label class="form-check-label">' +
                            '<input class="form-check-input" type="radio" name="deny_reason" value="' + this.denyReasonSubject + '"> не нужен предмет для поступления</label></div>' +
                            '<div class="form-check"><label class="form-check-label">' +
                            '<input class="form-check-input" type="radio" name="deny_reason" value="' + this.denyReasonOther + '"> другое</label></div>' +
                            '<textarea name="comment" class="form-control my-2" rows="3" placeholder="Комментарий"></textarea>' +
                            '<button name="set_deny_reason" class="btn btn-primary">сохранить</button> ' +
                        '</form>';
                }
                break;
        }
        $(e).html('<span class="text-nowrap welcome-lesson-buttons">' + contents + '</span>');
    },
    showRescheduleForm: function(e, lessonId, lessonDate) {
        let form = $("#welcome-lesson-reschedule-form");
        $(form).find(".welcome-lesson-id").val(lessonId);
        $(form).find(".date-select").val(lessonDate);
        $(form).find(".welcome-lesson-date").text(lessonDate);
        $("#welcome-lesson-reschedule-modal").modal('show');
        $(form).find(".datepicker").datepicker(Main.datepickerDefaultSettings);
    },
    showMovingForm: function(e, lessonId) {
        $(e).prop("disabled", true);
        $.ajax({
            url: '/welcome-lesson/propose-group',
            type: 'post',
            dataType: 'json',
            data: {id: lessonId}
        })
            .done(function (data) {
                if (data.status === "ok") {
                    let form = $("#welcome-lesson-moving-form");
                    $(form).find(".welcome-lesson-id").val(data.id);
                    $(form).find(".pupil-info").html(data.pupilName);
                    $(form).find(".welcome-lesson-start-date").html(data.lessonDate);
                    let proposals = '';
                    let checkProposal = (data.groupIds.length === 1);
                    data.groupIds.forEach(function (groupId) {
                        proposals += '<div class="form-check">' +
                            '<label class="form-check-label">' +
                            '<input class="form-check-input" type="radio" name="welcome_lesson[group_proposal]" ' +
                            'value="' + groupId + '" onchange="WelcomeLesson.groupChange(this);" ' + (checkProposal ? ' checked' : '') + ' required>' +
                            Main.groupMap[groupId].name + ' (' + Main.groupMap[groupId].teacher + ')' +
                            '</label>' +
                            '</div>';
                    });
                    $(form).find(".group-proposal").html(proposals);
                    let groupList = '';
                    Main.groupActiveList.forEach(function(groupId) {
                        if (data.groupIds.indexOf(groupId) < 0 && data.excludeGroupIds.indexOf(groupId) < 0) {
                            groupList += '<option value="' + groupId + '">' + Main.groupMap[groupId].name + ' (' + Main.groupMap[groupId].teacher + ')</option>';
                        }
                    });
                    $(form).find(".other-group").html(groupList);
                    $("#welcome-lesson-moving-modal").modal('show');
                } else {
                    Main.throwFlashMessage('#messages_place', "Ошибка: " + data.message, 'alert-danger');
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(function() {
                $(".welcome-lesson-buttons button").prop("disabled", false);
            });
    },
    groupChange: function(e) {
        $("#welcome-lesson-moving-form .other-group").prop("disabled", parseInt($(e).val()) !== 0 || !$(e).is(":checked"));
    },
    lockMovingFormButtons: function() {
        $("#welcome-lesson-moving-form").find('button').prop("disabled", true);
    },
    unlockMovingFormButtons: function() {
        $("#welcome-lesson-moving-form").find('button').prop("disabled", false);
    },
    lockRescheduleFormButtons: function() {
        $("#welcome-lesson-reschedule-form").find('button').prop("disabled", true);
    },
    unlockRescheduleFormButtons: function() {
        $("#welcome-lesson-reschedule-form").find('button').prop("disabled", false);
    },
    lockDenyDetailsFormButtons: function() {
        $(".deny-details-form button").prop("disabled", true);
    },
    unlockDenyDetailsFormButtons: function() {
        $(".deny-details-form button").prop("disabled", false);
    },
    reschedule: function(form) {
        this.lockRescheduleFormButtons();
        return $.ajax({
            url: '/welcome-lesson/reschedule',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === "ok") {
                    $("#welcome-lesson-reschedule-modal").modal("hide");
                    WelcomeLesson.setButtons($('tr[data-key="' + data.result.id + '"] td:last-child'), data.result);
                } else {
                    Main.throwFlashMessage('#welcome-lesson-reschedule-messages-place', "Ошибка: " + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#welcome-lesson-reschedule-messages-place');
            })
            .always(WelcomeLesson.unlockRescheduleFormButtons);
    },
    movePupil: function(form) {
        this.lockMovingFormButtons();
        return $.ajax({
                url: '/welcome-lesson/move',
                type: 'post',
                dataType: 'json',
                data: $(form).serialize()
            })
            .done(function(data) {
                if (data.status === "ok") {
                    $("#welcome-lesson-moving-modal").modal("hide");
                    WelcomeLesson.setButtons($('tr[data-key="' + data.result.id + '"] td:last-child'), data.result);
                } else {
                    Main.throwFlashMessage('#welcome-lesson-messages-place', "Ошибка: " + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#welcome-lesson-messages-place');
            })
            .always(WelcomeLesson.unlockMovingFormButtons);
    },
    setDenyDetails: function(id, form) {
        this.lockDenyDetailsFormButtons();
        $.ajax({
            url: '/welcome-lesson/set-deny-details?id=' + id,
            type: 'post',
            dataType: 'json',
            data: $(form).serialize(),
            success: function(data) {
                WelcomeLesson.unlockDenyDetailsFormButtons();
                if (data.status === 'ok') {
                    WelcomeLesson.setButtons($('tr[data-key="' + data.result.id + '"] td:last-child'), data.result);
                } else {
                    Main.throwFlashMessage('#messages_place', "Ошибка: " + data.message, 'alert-danger');
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                WelcomeLesson.unlockDenyDetailsFormButtons();
            }
        });
        return false;
    }
};
