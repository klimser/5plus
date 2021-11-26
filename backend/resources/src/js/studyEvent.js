let StudyEvent = {
    eventStatusUnknown: 0,
    eventStatusPassed: 1,
    eventStatusCancelled: 2,

    memberStatusUnknown: 0,
    memberStatusAttend: 1,
    memberStatusMiss: 2,

    serverTimestamp: null,
    clientTimestamp: null,
    limitTimestamp: null,
    eventData: null,
    isTeacher: true,
    isAdmin: false,
    
    eventModal: null,
    init: function(serverTimestamp, limitTimestamp, isTeacher = true, isAdmin = false) {
        this.serverTimestamp = serverTimestamp;
        this.clientTimestamp = Math.floor(Date.now() / 1000);
        this.limitTimestamp = limitTimestamp;
        this.isTeacher = isTeacher;
        this.isAdmin = isAdmin;
        this.eventModal = $("#modal-event");
    },
    openEvent: function(eventId) {
        $(this.eventModal).modal("show");
        this.loadEvent(eventId);
    },
    loadEvent: function(eventId) {
        this.eventData = null;
        $(this.eventModal).find("#event-messages-place").html('');
        $(this.eventModal).find("#event-content").html('<div class="loading-box"></div>');
        $.ajax({
            url: '/event/get?id=' + eventId,
            type: 'get',
            dataType: 'json'
        })
            .done(function (data) {
                if (data.status === 'ok') {
                    StudyEvent.eventData = data.eventData;
                    StudyEvent.renderEvent();
                } else {
                    Main.throwFlashMessage('#event-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function (xhr, textStatus, errorThrown) {
                $(StudyEvent.eventModal).find("#event-content").html('');
                Main.logAndFlashAjaxError(xhr, textStatus, errorThrown, '#event-messages-place');
            });
    },
    renderEvent: function() {
        if (null === this.eventData) {
            Main.throwFlashMessage('#event-messages-place', 'Event data missing, try to reload', 'alert-danger');
        }
        $(this.eventModal).find("#event-messages-place").html('');
        $(this.eventModal).find(".modal-title").text(this.eventData.name);
        $(this.eventModal).find("#event-time").text(this.eventData.time);
        if (!this.isTeacher) {
            $(this.eventModal).find("#event-teacher").text(this.eventData.teacher);
        }
        let content = '';
        switch (this.eventData.status) {
            case this.eventStatusUnknown:
                content = '<div class="card-body status_block">' +
                    '<div class="row">' +
                    '<div class="col">' +
                    '<button class="btn btn-success btn-block" title="Состоялось" onclick="StudyEvent.setEventStatus(' + this.eventStatusPassed + ');">' +
                    '<span class="fas fa-check"></span>' +
                    '</button>' +
                    '</div>' +
                    '<div class="col">' +
                    '<button class="btn btn-danger btn-block" title="Было отменено" onclick="StudyEvent.setEventStatus(' + this.eventStatusCancelled + ');">' +
                    '<span class="fas fa-times"></span>' +
                    '</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                break;
            case this.eventStatusPassed:
            case this.eventStatusCancelled:
                content = '';
                if (this.isAdmin) {
                    content += '<div class="card-body"><button class="btn btn-block btn-outline-secondary" onclick="StudyEvent.revertEventStatus();"><span class="fas fa-pencil-alt"></span></button></div>';
                }
                content += '<ul class="list-group list-group-flush students_block">' +
                    '<li class="list-group-item list-group-item-secondary text-center">Студенты</li>';
                    this.eventData.welcomeMembers.forEach(function(welcomeMember) {
                        let titleCssClass = '';
                        switch (welcomeMember.status) {
                            case WelcomeLesson.statusPassed:
                                titleCssClass = ' text-success ';
                                break;
                            case WelcomeLesson.statusMissed:
                                titleCssClass = ' text-danger ';
                                break;
                        }
                        content += '<li class="list-group-item list-group-item-warning p-2">' +
                            '<div id="messages_place_event_welcome_member_' + welcomeMember.id + '"></div>' +
                            '<div id="event_welcome_member_' + welcomeMember.id + '" class="event_welcome_member row no-gutters align-items-center ' + titleCssClass + '">' +
                            '<div class="col-8">' + welcomeMember.user.name +
                            '</div>' +
                            '<div class="col-4 buttons-column text-right">' + StudyEvent.getWelcomeButtonsColumn(welcomeMember) + '</div>' +
                            '</div>' +
                            '</li>';
                    });
                    this.eventData.members.forEach(function(member) {
                        let titleCssClass = '';
                        switch (member.status) {
                            case StudyEvent.memberStatusAttend:
                                titleCssClass = ' text-success ';
                                break;
                            case StudyEvent.memberStatusMiss:
                                titleCssClass = ' text-danger ';
                                break;
                        }
                        content += '<li class="list-group-item p-2">' +
                            '<div id="messages_place_event_member_' + member.id + '"></div>' +
                            '<div id="event_member_' + member.id + '" class="event_member row no-gutters align-items-center ' + titleCssClass + '">' +
                            '<div class="col-8">' + member.groupPupil.user.name +
                            (null !== member.groupPupil.debtMessage
                                ? ' <span class="fas fa-info-circle text-danger" data-toggle="tooltip" data-placement="top" data-html="true" title="' + member.groupPupil.debtMessage + '"></span>'
                                : '') +
                            '</div>' +
                            '<div class="col-4 buttons-column text-right">' + StudyEvent.getButtonsColumn(member, false) + '</div>' +
                            '</div>' +
                            '<div class="marks-form collapse">' + StudyEvent.getMarksForm(member) + '</div>' +
                            '</li>';
                    });
                content += '</ul>';
                break;
        }
        $(this.eventModal).find("#event-content").html(content);
        $(this.eventModal).find("#event-content").find('[data-toggle="tooltip"]').tooltip()
    },
    renderEventStatus: function() {
        let eventButton = $("#event-button-" + this.eventData.id);
        switch (this.eventData.status) {
            case this.eventStatusUnknown:
                $(eventButton).removeClass("btn-success btn-danger").addClass('btn-outline-dark');
                break;
            case this.eventStatusPassed:
                $(eventButton).removeClass("btn-outline-dark btn-danger").addClass('btn-success');
                break;
            case this.eventStatusCancelled:
                $(eventButton).removeClass("btn-outline-dark btn-success").addClass('btn-danger');
                break;
        }
    },
    fillMemberButtons: function(member) {
        let memberRow = $("#event_member_" + member.id);
        switch (member.status) {
            case this.memberStatusUnknown:
                $(memberRow).removeClass("text-success text-danger");
                break;
            case this.memberStatusAttend:
                $(memberRow).addClass("text-success").removeClass("text-danger");
                break;
            case this.memberStatusMiss:
                $(memberRow).addClass("text-danger").removeClass("text-success");
                break;
        }
        $(memberRow).find(".buttons-column").html(this.getButtonsColumn(member));
    },
    fillWelcomeMemberButtons: function(member) {
        let memberRow = $("#event_welcome_member_" + member.id);
        switch (member.status) {
            case WelcomeLesson.statusUnknown:
                $(memberRow).removeClass("text-success text-danger");
                break;
            case WelcomeLesson.statusPassed:
                $(memberRow).addClass("text-success").removeClass("text-danger");
                break;
            case WelcomeLesson.statusCanceled:
            case WelcomeLesson.statusMissed:
                $(memberRow).addClass("text-danger").removeClass("text-success");
                break;
        }
        $(memberRow).find(".buttons-column").html(this.getWelcomeButtonsColumn(member));
    },
    lockStatusButtons: function() {
        $(this.eventModal).find(".status_block button").prop("disabled", true);
    },
    unlockStatusButtons: function() {
        $(this.eventModal).find(".status_block button").prop("disabled", false);
    },
    setEventStatus: function(status) {
        this.lockStatusButtons();
        $.ajax({
                url: '/event/change-status?id=' + this.eventData.id,
                type: 'post',
                dataType: 'json',
                data: {
                    status: status
                }
            })
        .done(function(data) {
            if (data.status === 'ok') {
                StudyEvent.eventData = data.eventData;
                StudyEvent.renderEvent();
                StudyEvent.renderEventStatus();
            } else {
                Main.throwFlashMessage('#event-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                StudyEvent.unlockStatusButtons();
            }
        })
        .fail(function(xhr, textStatus, errorThrown) {
            Main.logAndFlashAjaxError(xhr, textStatus, errorThrown, '#event-messages-place');
            StudyEvent.unlockStatusButtons();
        });
    },
    isAttendEditAllowed: function() {
        if (this.eventData.status !== this.eventStatusPassed) return false;
        if (this.isAdmin) return true;
        let currentTime = this.serverTimestamp + (Math.floor(Date.now() / 1000) - this.clientTimestamp);
        return currentTime <= this.eventData.limitAttendTimestamp;
    },
    getButtonsColumn: function(member, fillMarks = true) {
        switch (member.status) {
            case this.memberStatusUnknown:
                return '<button class="btn btn-success" onclick="StudyEvent.setMemberStatus(' + member.id + ', ' + this.memberStatusAttend + ');" title="Присутствовал(а)">' +
                        '<span class="fas fa-check"></span>' +
                    '</button>' +
                    '<button class="btn btn-danger" onclick="StudyEvent.setMemberStatus(' + member.id + ', ' + this.memberStatusMiss + ');" title="Отсутствовал(а)">' +
                        '<span class="fas fa-times"></span>' +
                    '</button>';
            case this.memberStatusAttend:
                if (fillMarks) {
                    $("#event_member_" + member.id).closest(".list-group-item").find(".marks-form").html(this.getMarksForm(member));
                }
                let content = '<a href="#" class="btn btn-outline-' + this.getMarksClass(member) + ' marks" onclick="StudyEvent.toggleMarks(this); return false;">' + (member.mark ?? 0) + ' / ' + (member.markHomework ?? 0) + '</a>';
                if (this.isAttendEditAllowed()) {
                    content += ' <button class="btn btn-outline-dark" onclick="StudyEvent.revertMemberStatus(' + member.id + ');">' +
                            '<span class="fas fa-pencil-alt"></span>' +
                        '</button>';
                }
                return content;
            case this.memberStatusMiss:
                if (this.isAttendEditAllowed()) {
                    return '<button class="btn btn-outline-dark" onclick="StudyEvent.revertMemberStatus(' + member.id + ');">' +
                           '<span class="fas fa-pencil-alt"></span>' +
                        '</button>';
                }
                break;
        }
        return '';
    },
    getMarksClass: function(member) {
        if ((member.mark ?? 0) > 0 && (member.markHomework ?? 0) > 0) {
            return 'success';
        }
        if ((member.mark ?? 0) > 0 || (member.markHomework ?? 0) > 0) {
            return 'warning';
        }
        return 'secondary';
    },
    getMarksForm: function(member) {
        if (member.status !== this.memberStatusAttend) {
            return '';
        }
        return '<div class="row">' +
            '<div class="col"><div class="form-group">' +
            '<label>Оценка на занятии</label>' +
            '<div class="input-group">' +
            '<input type="number" step="1" min="1" max="5" class="form-control mark ' + ((member.mark ?? 0) > 0 ? 'is-valid' : '') + '" ' +
            'placeholder="Балл" title="Балл" value="' + ((member.mark ?? 0) > 0 ? member.mark : '') + '" required>' +
            '<div class="input-group-append">' +
            '<button class="btn btn-primary" onclick="StudyEvent.setMark(' + member.id + ', this);">OK</button>' +
            '</div>' +
            '</div>' +
            '</div></div>' +
            '<div class="col"><div class="form-group">' +
            '<label>Оценка за домашнее задание</label>' +
            '<div class="input-group">' +
            '<input type="number" step="1" min="1" max="5" class="form-control markHomework ' + ((member.markHomework ?? 0) > 0 ? 'is-valid' : '') + '" ' +
            'placeholder="Балл" title="Балл" value="' + ((member.markHomework ?? 0) > 0 ? member.markHomework : '') + '" required>' +
            '<div class="input-group-append">' +
            '<button class="btn btn-primary" onclick="StudyEvent.setMarkHomework(' + member.id + ', this);">OK</button>' +
            '</div>' +
            '</div>' +
            '</div></div>' +
            '</div>';
    },
    getWelcomeButtonsColumn: function(member) {
        if (member.status === WelcomeLesson.statusUnknown) {
            return '<button class="btn btn-success" onclick="StudyEvent.setWelcomeMemberStatus(' + member.id + ', ' + WelcomeLesson.statusPassed + ');" title="Присутствовал(а)">' +
                '<span class="fas fa-check"></span>' +
                '</button>' +
                '<button class="btn btn-danger" onclick="StudyEvent.setWelcomeMemberStatus(' + member.id + ', ' + WelcomeLesson.statusMissed + ');" title="Отсутствовал(а)">' +
                '<span class="fas fa-times"></span>' +
                '</button>';
        }
        return '';
    },
    lockMemberButtons: function(memberId) {
        $(this.eventModal).find(".students_block").find("button").prop("disabled", true);
        this.processingEventMemberId = memberId;
    },
    unlockMemberButtons: function() {
        $(StudyEvent.eventModal).find(".students_block").find("button").prop("disabled", false);
        StudyEvent.processingEventMemberId = null;
    },
    revertEventStatus: function() {
        let actualStatus = this.eventData.status;
        this.eventData.status = this.eventStatusUnknown;
        this.renderEvent();
        this.eventData.status = actualStatus;
    },
    revertMemberStatus: function(memberId) {
        let memberRow = $("#event_member_" + memberId);
        $(memberRow).find(".buttons-column")
            .html(this.getButtonsColumn({id: memberId, status: this.memberStatusUnknown}));
        $(memberRow).closest(".list-group-item").find(".marks-form").html('');
    },
    setMemberStatus: function(memberId, status) {
        this.lockMemberButtons(memberId);
        $.ajax({
                url: '/event/set-member-status?id=' + memberId,
                type: 'post',
                dataType: 'json',
                data: {
                    status: status
                }
            })
            .done(function(data) {
                if (data.status === 'ok') {
                    StudyEvent.fillMemberButtons(data.member);
                } else {
                    Main.throwFlashMessage('#messages_place_event_member_' + StudyEvent.processingEventMemberId, 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(xhr, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(xhr, textStatus, errorThrown, '#messages_place_event_member_' + StudyEvent.processingEventMemberId);
            })
            .always(StudyEvent.unlockMemberButtons);
    },
    setWelcomeMemberStatus: function(id, status) {
        this.lockMemberButtons(id);
        $.ajax({
            url: '/event/set-welcome-member-status?id=' + id,
            type: 'post',
            dataType: 'json',
            data: {
                status: status
            }
        })
            .done(function (data) {
                if (data.status === 'ok') {
                    StudyEvent.fillWelcomeMemberButtons(data.member);
                } else {
                    Main.throwFlashMessage('#messages_place_event_welcome_member_' + StudyEvent.processingEventMemberId, 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function (xhr, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(xhr, textStatus, errorThrown, '#messages_place_event_welcome_member_' + StudyEvent.processingEventMemberId);
            })
            .always(StudyEvent.unlockMemberButtons);
    },
    toggleMarks: function(e) {
        $(e).closest(".list-group-item").find(".marks-form").collapse('toggle');
    },
    setMark: function(memberId, e) {
        return this.setMarks($(e).closest(".form-group").find(".mark"), 'mark', memberId);
    },
    setMarkHomework: function(memberId, e) {
        return this.setMarks($(e).closest(".form-group").find(".markHomework"), 'mark_homework', memberId);
    },
    setMarks: function(markInput, dataKey, memberId) {
        $(markInput).removeClass("is-invalid").removeClass("is-valid");
        let mark = parseInt($(markInput).val());
        if (!mark || mark <= 0 || mark > 5) {
            $(markInput).addClass("is-invalid");
            return false;
        }

        this.lockMemberButtons(memberId);
        $.ajax({
            url: '/event/set-mark?memberId=' + memberId,
            type: 'post',
            dataType: 'json',
            data: {[dataKey]: mark}
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    let marksButton = $("#event_member_" + data.member.id).find(".marks");
                    let marksForm = $(marksButton).closest('li').find('.marks-form');
                    $(marksButton).text((data.member.mark ?? 0) + ' / ' + (data.member.markHomework ?? 0));
                    $(marksButton).removeClass('btn-outline-success').removeClass('btn-outline-warning').removeClass('btn-outline-secondary')
                        .addClass('btn-outline-' + StudyEvent.getMarksClass(data.member));
                    if ((data.member.mark ?? 0) > 0) {
                        $(marksForm).find('.mark').addClass("is-valid");
                    }
                    if ((data.member.markHomework ?? 0) > 0) {
                        $(marksForm).find('.markHomework').addClass("is-valid");
                    }
                } else {
                    Main.throwFlashMessage('#messages_place_event_member_' + StudyEvent.processingEventMemberId, 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place_event_member_' + StudyEvent.processingEventMemberId, "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            })
            .always(StudyEvent.unlockMemberButtons);
        return false;
    }
};
