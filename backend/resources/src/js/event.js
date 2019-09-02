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
    jumpToDate: function() {
        window.location = "/event/index?date=" + $("#jump_to_date").val();
    },
    toggleEvent: function(eventId) {
        let detailsBlock = $("#event_details_" + eventId);
        if ($(detailsBlock).hasClass("hidden")) {
            $(detailsBlock).removeClass("hidden");
            let pupilsBlock = $(detailsBlock).find(".pupils_block");
            if (parseInt($(pupilsBlock).data("buttonState")) === 0) {
                $(pupilsBlock).find(".event_member").each(function() {
                    Event.fillMemberButtons($(this).data("id"));
                });
                $(pupilsBlock).data("buttonState", 1);
            }
        } else {
            $(detailsBlock).addClass("hidden");
        }
    },
    fillMemberButtons: function(memberId) {
        let memberRow = $("#event_member_" + memberId);
        switch ($(memberRow).data("status")) {
            case this.memberStatusUnknown:
                $(memberRow).removeClass("success danger");
                break;
            case this.memberStatusAttend:
                $(memberRow).addClass("success").removeClass("danger");
                break;
            case this.memberStatusMiss:
                $(memberRow).addClass("danger").removeClass("success");
                break;
        }
        $(memberRow).find(".buttons-column").html(this.getButtonsColumn(memberId, $(memberRow).data('status'), $(memberRow).data('mark')));
    },
    lockStatusButtons: function(eventId) {
        $('#event_details_' + eventId).find(".status_block button").prop("disabled", true);
    },
    unlockStatusButtons: function() {
        $(".status_block").each(function() {
            $(this).find("button").prop("disabled", false);
        });
    },
    changeStatus: function(eventId, status) {
        this.lockStatusButtons(eventId);
        $.ajax({
            url: '/event/change-status?event=' + eventId,
            type: 'post',
            dataType: 'json',
            data: {
                status: status
            },
            success: function(data) {
                if (data.status === 'ok') {
                    let eventDetailsBlock = $('#event_details_' + data.eventId);
                    $(eventDetailsBlock).data("status", data.eventStatus).find(".status_block").remove();
                    let pupilsBlock = $(eventDetailsBlock).find(".pupils_block");
                    if (data.eventStatus === this.eventStatusCancelled) {
                        $(pupilsBlock).find(".event_member").each(function() {
                            $(this).data("status", Event.memberStatusMiss);
                            Event.fillMemberButtons($(this).data("id"));
                        });
                    }
                    $(pupilsBlock).removeClass("hidden");
                } else {
                    Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                    Event.unlockStatusButtons();
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                Event.unlockStatusButtons();
            }
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
                break;
            case this.memberStatusAttend:
                if (memberMark > 0) return '<b>' + memberMark + '</b>';
                else return '<form onsubmit="return Event.setPupilMark(this, ' + memberId + ');">' +
                    '<div class="input-group"><input type="number" name="mark" step="1" min="1" max="5" class="form-control" placeholder="Балл" required>' +
                    '<span class="input-group-btn">' +
                        '<button class="btn btn-primary">OK</button>' +
                '</span></div></form>';
                break;
            case this.memberStatusMiss:
                if (this.isAttendEditAllowed(memberId)) {
                    return '<button class="btn btn-default" onclick="Event.revertMissStatus(this, ' + memberId + ');">' +
                           '<span class="fas fa-pencil-alt"></span>' +
                        '</button>';
                }
                break;
        }
        return '';
    },
    lockMemberButtons: function() {
        $(".pupils_block").find("button").prop("disabled", true);
    },
    unlockMemberButtons: function() {
        $(".pupils_block").find("button").prop("disabled", false);
    },
    revertMissStatus: function(e, memberId) {
        let memberRow = $("#event_member_" + memberId);
        $(memberRow).find(".buttons-column")
            .html(this.getButtonsColumn(memberId, this.isAttendEditAllowed(memberId) ? this.memberStatusUnknown : this.memberStatusMiss, $(memberRow).data('mark')));
    },
    setPupilAttendStatus: function(memberId, status) {
        this.lockMemberButtons();
        $.ajax({
            url: '/event/set-pupil-status?memberId=' + memberId,
            type: 'post',
            dataType: 'json',
            data: {
                status: status
            },
            success: function(data) {
                if (data.status === 'ok') {
                    let memberBlock = $("#event_member_" + data.memberId);
                    $(memberBlock).data("status", data.memberStatus);
                    Event.fillMemberButtons(data.memberId);
                } else {
                    Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                }
                Event.unlockMemberButtons();
            },
            error: function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                Event.unlockMemberButtons();
            }
        });
    },
    setPupilMark: function(e, memberId) {
        let mark = parseInt($(e).find("input[name='mark']").val());
        if (!mark || mark <= 0 || mark > 5) {
            Main.throwFlashMessage('#messages_place', "Укажите оценку от 1 до 5", 'alert-danger');
            return false;
        }
        this.lockMemberButtons(memberId);
        $.ajax({
            url: '/event/set-pupil-mark?member=' + memberId,
            type: 'post',
            dataType: 'json',
            data: {
                mark: mark
            },
            success: function(data) {
                if (data.status === 'ok') {
                    $("#event_member_" + data.memberId).data('mark', data.memberMark);
                    Event.fillMemberButtons(data.memberId);
                } else {
                    Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                    Event.unlockMemberButtons();
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                Event.unlockMemberButtons();
            }
        });
        return false;
    }
};
