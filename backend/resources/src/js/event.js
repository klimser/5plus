var Event = {
    jumpToDate: function(e) {
        var date = $("#jump_to_date").val();
        window.location = "/event/index?date=" + date;
    },
    toggleEvent: function(eventId) {
        var detailsBlock = $("#event_details_" + eventId);
        if ($(detailsBlock).hasClass("hidden")) $(detailsBlock).removeClass("hidden");
        else $(detailsBlock).addClass("hidden");
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
                    var eventDetailsBlock = $('#event_details_' + data.eventId);
                    $(eventDetailsBlock).find(".status_block").remove();
                    if (data.eventStatus === '2') {
                        $(eventDetailsBlock).find(".pupils_block table tbody tr").each(function(){
                            $(this).addClass("danger");
                            $(this).find(".buttons-column").html('');
                        });
                    }
                    $(eventDetailsBlock).find(".pupils_block").removeClass("hidden");
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
    lockMemberButtons: function() {
        $(".pupils_block").find("button").prop("disabled", true);
    },
    unlockMemberButtons: function() {
        $(".pupils_block").find("button").prop("disabled", false);
    },
    setPupilAttendStatus: function(memberId, status) {
        this.lockMemberButtons();
        $.ajax({
            url: '/event/set-pupil-status?member=' + memberId,
            type: 'post',
            dataType: 'json',
            data: {
                status: status
            },
            success: function(data) {
                if (data.status === 'ok') {
                    var memberBlock = $("#event_member_" + data.memberId);
                    var buttonsColumn = $(memberBlock).find(".buttons-column");
                    if (data.memberStatus === '1') {
                        $(memberBlock).addClass('success');
                        $(buttonsColumn).html(Event.getPupilMarkBlock(data.memberId));
                    } else {
                        $(memberBlock).addClass('danger');
                        $(buttonsColumn).html('');
                    }
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
        var mark = $(e).closest(".input-group").find("input").val();
        if (!mark.length) {
            Main.throwFlashMessage('#messages_place', "Укажите оценку", 'alert-danger');
            return false;
        }
        mark = parseInt(mark);
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
                    var buttonsColumn = $("#event_member_" + data.memberId).find(".buttons-column");
                    $(buttonsColumn).addClass("text-center").html('<b>' + data.memberMark + '</b>');
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
    },
    getPupilMarkBlock: function(memberId) {
        return '<div class="input-group">' +
            '<input type="number" step="1" min="1" max="5" class="form-control" placeholder="Балл">' +
            '<span class="input-group-btn">' +
                '<button class="btn btn-primary" type="button" onclick="Event.setPupilMark(this, ' + memberId + ');">' +
                    'OK' +
                '</button>' +
            '</span>' +
        '</div>';
    }
};