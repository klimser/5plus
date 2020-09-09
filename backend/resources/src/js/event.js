let Event = {
    eventStatusUnknown: 0,
    eventStatusPassed: 1,
    eventStatusCancelled: 2,

    memberStatusUnknown: 0,
    memberStatusAttend: 1,
    memberStatusMiss: 2,

    serverTimestamp: null,
    clientTimestamp: null,
    init: function(serverTimestamp) {
        this.serverTimestamp = serverTimestamp;
        this.clientTimestamp = Math.floor(Date.now() / 1000);
    },
    toggleEvent: function(eventId) {
        let detailsBlock = $("#event_details_" + eventId);
        if (parseInt($(detailsBlock).data('status')) !== this.eventStatusUnknown) {
            let pupilsBlock = $(detailsBlock).find(".pupils_block");
            if (parseInt($(pupilsBlock).data("buttonState")) === 0) {
                $(pupilsBlock).find(".event_member").each(function () {
                    Event.fillMemberButtons($(this).data("id"));
                });
                $(pupilsBlock).find(".event_welcome_member").each(function () {
                    Event.fillWelcomeMemberButtons($(this).data("id"));
                });
                $(pupilsBlock).data("buttonState", 1);
            }
        }
        $(detailsBlock).collapse('toggle');
    },
    fillMemberButtons: function(memberId) {
        let memberRow = $("#event_member_" + memberId);
        switch ($(memberRow).data("status")) {
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
        $(memberRow).find(".buttons-column").html(this.getButtonsColumn(memberId, $(memberRow).data('status'), $(memberRow).data('mark')));
    },
    fillWelcomeMemberButtons: function(memberId) {
        let memberRow = $("#event_welcome_member_" + memberId);
        switch ($(memberRow).data("status")) {
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
        $(memberRow).find(".buttons-column").html(this.getWelcomeButtonsColumn(memberId, $(memberRow).data('status')));
    },
    lockStatusButtons: function(eventId) {
        $('#event_details_' + eventId).find(".status_block button").prop("disabled", true);
        this.processingEventId = eventId;
    },
    unlockStatusButtons: function() {
        $(".status_block").each(function() {
            $(this).find("button").prop("disabled", false);
        });
        this.processingEventId = null;
    },
    changeStatus: function(eventId, status) {
        this.lockStatusButtons(eventId);
        $.ajax({
                url: '/event/change-status?event=' + eventId,
                type: 'post',
                dataType: 'json',
                data: {
                    status: status
                }
            })
        .done(function(data) {
            if (data.status === 'ok') {
                let eventDetailsBlock = $('#event_details_' + data.eventId);
                $(eventDetailsBlock).data("status", data.eventStatus).find(".status_block").remove();
                let pupilsBlock = $(eventDetailsBlock).find(".pupils_block");
                $(pupilsBlock).find(".event_member").each(function() {
                    if (data.eventStatus === Event.eventStatusCancelled) {
                        $(this).data("status", Event.memberStatusMiss);
                    }
                    Event.fillMemberButtons($(this).data("id"));
                });
                $(pupilsBlock).find(".event_welcome_member").each(function () {
                    if (data.eventStatus === Event.eventStatusCancelled) {
                        $(this).data("status", WelcomeLesson.statusCanceled);
                    }
                    Event.fillWelcomeMemberButtons($(this).data("id"));
                });
                
                $(pupilsBlock).collapse('show');
            } else {
                Main.throwFlashMessage('#messages_place_event_' + Event.processingEventId, 'Ошибка: ' + data.message, 'alert-danger');
                Event.unlockStatusButtons();
            }
        })
        .fail(function(xhr, textStatus, errorThrown) {
            Main.throwFlashMessage('#messages_place_event_' + Event.processingEventId, "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            Event.unlockStatusButtons();
        });
    },
    isAttendEditAllowed: function(memberId) {
        let eventDetailsBlock = $("#event_member_" + memberId).closest(".event_details");
        if ($(eventDetailsBlock).data("status") !== this.eventStatusPassed) return false;
        let limitTimestamp = $(eventDetailsBlock).data("limitAttendTimestamp");
        let currentTime = this.serverTimestamp + (Math.floor(Date.now() / 1000) - this.clientTimestamp);
        return currentTime <= limitTimestamp;
    },
    getButtonsColumn: function(memberId, memberStatus, memberMark) {
        switch (memberStatus) {
            case this.memberStatusUnknown:
                return '<button class="btn btn-success" onclick="Event.setPupilAttendStatus(' + memberId + ', ' + this.memberStatusAttend + ');" title="Присутствовал(а)">' +
                        '<span class="fas fa-check"></span>' +
                    '</button>' +
                    '<button class="btn btn-danger" onclick="Event.setPupilAttendStatus(' + memberId + ', ' + this.memberStatusMiss + ');" title="Отсутствовал(а)">' +
                        '<span class="fas fa-times"></span>' +
                    '</button>';
            case this.memberStatusAttend:
                if (memberMark > 0) {
                    return '<b>' + memberMark + '</b>';
                }
                
                return '<form onsubmit="return Event.setPupilMark(this, ' + memberId + ');">' +
                    '<div class="input-group">' +
                    '<input type="number" name="mark" step="1" min="1" max="5" class="form-control" placeholder="Балл" title="Балл" required>' +
                    '<div class="input-group-append">' +
                        '<button class="btn btn-primary">OK</button>' +
                    '</div></div></form>';
            case this.memberStatusMiss:
                if (this.isAttendEditAllowed(memberId)) {
                    return '<button class="btn btn-outline-dark" onclick="Event.revertMissStatus(this, ' + memberId + ');">' +
                           '<span class="fas fa-pencil-alt"></span>' +
                        '</button>';
                }
                break;
        }
        return '';
    },
    getWelcomeButtonsColumn: function(memberId, memberStatus) {
        if (memberStatus === WelcomeLesson.statusUnknown) {
            return '<button class="btn btn-success" onclick="Event.setPupilAttendStatus(' + memberId + ', ' + WelcomeLesson.statusPassed + ', \'welcomeMemberId\');" title="Присутствовал(а)">' +
                '<span class="fas fa-check"></span>' +
                '</button>' +
                '<button class="btn btn-danger" onclick="Event.setPupilAttendStatus(' + memberId + ', ' + WelcomeLesson.statusMissed + ', \'welcomeMemberId\');" title="Отсутствовал(а)">' +
                '<span class="fas fa-times"></span>' +
                '</button>';
        }
        return '';
    },
    lockMemberButtons: function(memberId) {
        $(".pupils_block").find("button").prop("disabled", true);
        this.processingEventMemberId = memberId;
    },
    unlockMemberButtons: function() {
        $(".pupils_block").find("button").prop("disabled", false);
        this.processingEventMemberId = null;
    },
    revertMissStatus: function(e, memberId) {
        let memberRow = $("#event_member_" + memberId);
        $(memberRow).find(".buttons-column")
            .html(this.getButtonsColumn(memberId, this.isAttendEditAllowed(memberId) ? this.memberStatusUnknown : this.memberStatusMiss, $(memberRow).data('mark')));
    },
    setPupilAttendStatus: function(memberId, status, key) {
        if (key === undefined) {
            key = 'memberId';
        }
        this.lockMemberButtons(memberId);
        $.ajax({
                url: '/event/set-pupil-status?' + key + '=' + memberId,
                type: 'post',
                dataType: 'json',
                data: {
                    status: status
                }
            })
        .done(function(data) {
            if (data.status === 'ok') {
                if (data.memberId) {
                    let memberBlock = $("#event_member_" + data.memberId);
                    $(memberBlock).data("status", data.memberStatus);
                    Event.fillMemberButtons(data.memberId);
                } else {
                    let memberBlock = $("#event_welcome_member_" + data.welcomeMemberId);
                    $(memberBlock).data("status", data.memberStatus);
                    Event.fillWelcomeMemberButtons(data.welcomeMemberId);
                }
            } else {
                Main.throwFlashMessage('#messages_place_event_member_' + Event.processingEventMemberId, 'Ошибка: ' + data.message, 'alert-danger');
            }
            Event.unlockMemberButtons();
        })
        .fail(function(xhr, textStatus, errorThrown) {
            Main.throwFlashMessage('#messages_place_event_member_' + Event.processingEventMemberId, "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            Event.unlockMemberButtons();
        });
    },
    setPupilMark: function(e, memberId) {
        let mark = parseInt($(e).find("input[name='mark']").val());
        if (!mark || mark <= 0 || mark > 5) {
            Main.throwFlashMessage('#messages_place_event_member_' + Event.processingEventMemberId, "Укажите оценку от 1 до 5", 'alert-danger');
            return false;
        }
        this.lockMemberButtons(memberId);
        $.ajax({
                url: '/event/set-pupil-mark?member=' + memberId,
                type: 'post',
                dataType: 'json',
                data: {
                    mark: mark
                }
            })
        .done(function(data) {
            if (data.status === 'ok') {
                $("#event_member_" + data.memberId).data('mark', data.memberMark);
                Event.fillMemberButtons(data.memberId);
            } else {
                Main.throwFlashMessage('#messages_place_event_member_' + Event.processingEventMemberId, 'Ошибка: ' + data.message, 'alert-danger');
            }
            Event.unlockMemberButtons();
        })
        .fail(function(xhr, textStatus, errorThrown) {
            Main.throwFlashMessage('#messages_place_event_member_' + Event.processingEventMemberId, "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            Event.unlockMemberButtons();
        });
        return false;
    }
};
