let GroupMove = {
    init: function() {
        Main.initAutocompleteUser($("#pupil-to-move"));
        Main.loadGroups()
            .done(function(groupIds) {
                let elem = $("#group_to");
                $(elem).html('');
                groupIds.forEach(function(groupId) {
                    $(elem).append('<option value="' + groupId + '">' + Main.groupMap[groupId].name + ' (' + Main.groupMap[groupId].teacher + ')</option>');
                });
            });
    },
    loadGroups: function () {
        let pupilId = $("#pupil-id").val();
        let groupFrom = $("#group_from");
        $(groupFrom).html('');
        if (pupilId > 0 && $(groupFrom).data("pupil") !== pupilId) {
            $(groupFrom).html('<option>загрузка...</option>');
            $(groupFrom).prop('disabled', true);
            $.ajax({
                url: '/group/list-json',
                data: {
                    pupilId: pupilId
                },
                dataType: 'json'
            })
                .done(function (data) {
                    let groupFrom = $("#group_from");
                    let htmlAddon = '';
                    let activeGroupPupil = $(groupFrom).data('groupPupil');
                    data.forEach(function(groupData) {
                        let group = Main.groupMap[groupData.group_id];
                        htmlAddon += '<option value="' + groupData.id + '" data-start="' + groupData.date_start + '" ' +
                            'data-end="' + groupData.date_end + '" ' + (groupData.id === activeGroupPupil ? ' selected ' : '') + '>' +
                            group.name + '</option>';
                    });
                    $(groupFrom).html(htmlAddon);
                    $(groupFrom).data("pupil", $("#pupil-id").val());
                    $(groupFrom).change();
                })
                .fail(Main.logAndFlashAjaxError)
                .always(function() {
                    $("#group_from").prop('disabled', false);
                });
        }
    },
    selectGroup: function(e) {
        $("#group-move-id").val($(e).val());
        return this.setGroupFromDateInterval(e);
    },
    setGroupFromDateInterval: function (elem) {
        let chosenOption = $(elem).find("option:selected");
        $(elem).data('groupPupil', $(elem).val());
        let dateFromElem = $("#date_from");
        $(dateFromElem).datepicker("option", "minDate", $(chosenOption).data("start"));
        $(dateFromElem).datepicker("option", "maxDate", $(chosenOption).data("end"));

        if ($(dateFromElem).datepicker("getDate") !== null
            && ($(dateFromElem).datepicker("getDate") < $(dateFromElem).datepicker("option", "minDate")
                || ($(dateFromElem).datepicker("option", "maxDate") !== null
                    && $(dateFromElem).datepicker("getDate") > $(dateFromElem).datepicker("option", "maxDate")))) {
            $(dateFromElem).datepicker("setDate", null);
        }
    },
    setGroupToDateInterval: function (elem) {
        let groupId = $(elem).val();
        let dateToElem = $("#group-move-date-to");
        $(dateToElem).datepicker("option", "minDate", new Date(Main.groupMap[groupId].dateStart));
        let endDate = Main.groupMap[groupId].dateEnd;
        if (endDate !== null) {
            endDate = new Date(endDate);
        }
        $(dateToElem).datepicker("option", "maxDate", endDate);

        if ($(dateToElem).datepicker("getDate") !== null
            && ($(dateToElem).datepicker("getDate") < $(dateToElem).datepicker("option", "minDate")
                || ($(dateToElem).datepicker("option", "maxDate") !== null
                    && $(dateToElem).datepicker("getDate") > $(dateToElem).datepicker("option", "maxDate")))) {
            $(dateToElem).datepicker("setDate", null);
        }
    },
    movePupil: function () {
        let groupFromElem = $("#group_from");
        let groupToElem = $("#group_to");
        if ($(groupFromElem).val() === $(groupToElem).val()) {
            Main.throwFlashMessage("#messages_place", 'Невозможно перевести в ту же группу', 'alert-danger');
            return;
        }
        this.lockMoveButton();
        $.ajax({
            url: "/group/process-move-pupil",
            type: 'post',
            dataType: 'json',
            data: {
                "group-move": {
                    id: $("#group-move-id").val(),
                    group_id: $(groupToElem).val(),
                    date_from: $("#group-move-date-from").val(),
                    date_to: $("#group-move-date-to").val()
                }
            }
        })
            .done(function (data) {
                if (data.status === 'ok') {
                    Main.throwFlashMessage('#messages_place', 'Студент переведён', 'alert-success');
                    $("#move-pupil-form").collapse('hide');
                } else {
                    Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(GroupMove.unlockMoveButton);
    },
    lockMoveButton: function () {
        $("#move_pupil_button").prop('disabled', true);
    },
    unlockMoveButton: function () {
        $("#move_pupil_button").prop('disabled', false);
    },
};
