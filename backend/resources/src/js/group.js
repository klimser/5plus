var Group = {
    isNew: true,
    startDate: null,
    active: '',
    teacherList: [],
    teacherMap: [],
    dateRegexp: /\d{2}\.\d{2}\.\d{4}/,
    loadTeacherMap: function () {
        if (this.teacherMap.length == 0) {
            this.active = $("#group-teacher_id").val();
            $.ajax({
                url: '/teacher/list-json',
                type: 'get',
                dataType: 'json',
                success: function (data) {
                    for (var i = 0; i < data.length; i++) Group.teacherMap[data[i].id] = data[i].name;
                    Group.loadTeacherSelect($("#group-subject_id"));
                }
            });
        }
    },
    loadTeacherSelect: function (e) {
        var subjectId = $(e).val();
        $("#group-teacher_id").data("subject", subjectId);

        if (typeof this.teacherList[subjectId] == 'undefined') {
            $.ajax({
                url: '/teacher/list-json',
                type: 'get',
                dataType: 'json',
                data: {subject: subjectId},
                success: function (data) {
                    var tList = [];
                    for (var i = 0; i < data.teachers.length; i++) tList.push(data.teachers[i]);
                    Group.teacherList[data.subjectId] = tList;
                    Group.fillTeacherSelect(data.subjectId);
                }
            });
        } else this.fillTeacherSelect(subjectId);
    },
    fillTeacherSelect: function (subjectId) {
        if ($("#group-teacher_id").data("subject") == subjectId)
            $("#group-teacher_id").html(this.getTeachersOptions(subjectId)).removeData("subject");
    },
    getTeachersOptions: function (subjectId) {
        var list = '';
        for (var i = 0; i < this.teacherList[subjectId].length; i++) {
            list += '<option value="' + this.teacherList[subjectId][i] + '"' +
                (this.teacherList[subjectId][i] == this.active ? ' selected' : '') + '>' + this.teacherMap[this.teacherList[subjectId][i]] + '</option>';
        }
        return list;
    },
    pupilsMap: [],
    pupilsActive: [],
    loadPupilsMap: function () {
        if (this.pupilsMap.length == 0) {
            $.ajax({
                url: '/user/pupils',
                dataType: 'json',
                success: function (data) {
                    for (var i = 0; i < data.length; i++) Group.pupilsMap.push(data[i]);
                }
            });
        }
    },
    renderPupilForm: function () {
        var pupilSelect = '<select name="pupil[]" class="form-control chosen pupil-id"><option value=""></option>';
        for (var i = 0; i < this.pupilsMap.length; i++) {
            var used = false;
            for (var j = 0; j < this.pupilsActive.length; j++) if (this.pupilsMap[i].id == this.pupilsActive[j]) {
                used = true;
                break;
            }
            if (!used) pupilSelect += '<option value="' + this.pupilsMap[i].id + '">' + this.pupilsMap[i].name + '</option>';
        }
        pupilSelect += '</select>';

        var formHtml = '<div class="row form-group row-pupil"><div class="col-xs-12">' +
            '<div class="row">' +
            '<div class="col-xs-9 col-sm-10 col-md-11">' + pupilSelect + '</div>' +
            '<div class="col-xs-3 col-sm-2 col-md-1"><button type="button" class="btn btn-default" onclick="return Group.removePupil(this);" title="Удалить">' +
            '<span class="fas fa-user-minus"></span></button></div>' +
            '</div>';
        if (!this.isNew) {
            formHtml += '<div class="row"><div class="col-xs-12">' +
                '<div class="form-group"><div class="input-group input-daterange pupil-daterange">' +
                '<input type="text" class="form-control pupil-start" name="pupil_start[]"><div class="input-group-addon">до</div>' +
                '<input type="text" class="form-control pupil-end" name="pupil_end[]"></div></div></div>' +
                '</div>';
        }
        formHtml += '</div></div>';
        $("#group_pupils").append(formHtml);
        $('#group_pupils .row-pupil:last .chosen').chosen({
            disable_search_threshold: 6,
            no_results_text: 'Студент не найден',
            placeholder_text_single: 'Выберите студента'
        });
        if (!this.isNew) {
            $('#group_pupils .input-daterange:last').datepicker({
                autoclose: true,
                format: "dd.mm.yyyy",
                language: "ru",
                startDate: this.startDate
            });
        }
        return false;
    },
    removePupil: function (e) {
        if (confirm('Вы уверены?')) {
            $(e).closest("div.row-pupil").remove();
        }
        return false;
    },
    submitForm: function () {
        var validForm = true;
        var startDate = null, endDate = null;
        var startString = $("#group-date_start").val();
        var endString = $("#group-date_end").val();

        if (startString.length > 0) {
            if (!this.dateRegexp.test(startString)) {
                $("#group_date").addClass('has-error');
                validForm = false;
            } else {
                startDate = new Date(parseInt(startString.substr(6)), parseInt(startString.substr(3, 2)), parseInt(startString.substr(0, 2)));
            }
        }
        if (endString.length > 0) {
            if (!this.dateRegexp.test(endString)) {
                $("#group_date").addClass('has-error');
                validForm = false;
            } else {
                endDate = new Date(parseInt(endString.substr(6)), parseInt(endString.substr(3, 2)), parseInt(endString.substr(0, 2)));
            }
        }
        if (validForm) {
            if (startDate === null || (endDate !== null && endDate <= startDate)) {
                $("#group_date").addClass('has-error');
                validForm = false;
            }
        }
        if (validForm) $("#group_date").removeClass('has-error');

        $("select.pupil-id").each(function () {
            if (!$(this).val().length) {
                $(this).closest(".form-group").addClass('has-error');
                validForm = false;
            } else $(this).closest(".form-group").removeClass('has-error');
        });
        if (validForm) {
            $(".pupil-start").each(function () {
                startString = $(this).val();
                endString = $(this).closest(".form-group").find('.pupil-end').val();
                if (!startString.length || !Group.dateRegexp.test(startString) || (endString.length && !Group.dateRegexp.test(endString))) {
                    $(this).closest(".form-group").addClass('has-error');
                    validForm = false;
                } else {
                    if (endString.length) {
                        var pupilStartDate = new Date(parseInt(startString.substr(6)), parseInt(startString.substr(3, 2)), parseInt(startString.substr(0, 2)));
                        var pupilEndDate = new Date(parseInt(endString.substr(6)), parseInt(endString.substr(3, 2)), parseInt(endString.substr(0, 2)));

                        if (pupilStartDate < startDate || (endDate !== null && pupilEndDate > endDate) || pupilEndDate <= pupilStartDate) {
                            $(this).closest(".form-group").addClass('has-error');
                            validForm = false;
                        } else $(this).closest(".form-group").removeClass('has-error');
                    } else $(this).closest(".form-group").removeClass('has-error');
                }
            });
        }

        if (!validForm) $("#form-valid").addClass('bg-danger').text('Исправьте ошибки в форме');
        else $("#form-valid").removeClass('bg-danger').text('');

        return validForm;
    },
    toggleWeekday: function (e) {
        if ($(e).is(':checked')) {
            $(e).closest('.form-group').find("input.weektime").prop('disabled', false);
        } else {
            $(e).closest('.form-group').find("input.weektime").prop('disabled', true);
        }
    },
    loadPupils: function() {
        if ($("#pupil").html().length === 0) {
            $.ajax({
                url: '/user/pupils',
                dataType: 'json',
                success: function(data) {
                    var htmlAddon = '<option value=""></option>';
                    var selectedUser = $("#pupil").data("pupil");
                    for (var i = 0; i < data.length; i++) htmlAddon += '<option value="' + data[i].id + '"' + (selectedUser == data[i].id ? ' selected' : '') + '>' + data[i].name + '</option>';
                    $("#pupil").html(htmlAddon).chosen({
                        disable_search_threshold: 6,
                        no_results_text: 'Студент не найден',
                        placeholder_text_single: 'Выберите студента'
                    });
                    Group.loadGroups();
                }
            });
        }
    },
    loadGroups: function () {
        if ($("#pupil").val() && $("#group_from").data("pupil") != $("#pupil").val()) {
            $.ajax({
                url: '/group/list-json',
                data: {
                    pupilId: $("#pupil").val()
                },
                dataType: 'json',
                success: function (data) {
                    var htmlAddon = '';
                    var activeGroup = $("#group_from").data('group');
                    for (var i = 0; i < data.length; i++) {
                        htmlAddon += '<option value="' + data[i].id + '" data-start="' + data[i].startDate + '" data-end="' + data[i].endDate + '" ' +
                            (data[i].id == activeGroup ? ' selected ' : '') + '>' +
                            data[i].name + '. Цена: ' + data[i].lesson_price + ', со скидкой: ' + data[i].lesson_price_discount +
                            '</option>';
                    }
                    $("#group_from").html(htmlAddon);
                    $("#group_from").data("pupil", $("#pupil").val());
                    $("#group_from").change();
                }
            });
        }
    },
    setMoveDateInterval: function (elem) {
        $("#move_date").parent().datepicker("setStartDate", $(elem).find("option:selected").data("start"));
        $("#move_date").parent().datepicker("setEndDate", $(elem).find("option:selected").data("end"));
        if ($("#move_date").parent().datepicker("getDate") < $("#move_date").parent().datepicker("getStartDate")
            || $("#move_date").parent().datepicker("getDate") > $("#move_date").parent().datepicker("getEndDate")) {
            $("#move_date").parent().datepicker("clearDates");
        }
    },
    movePupil: function () {
        if (!$("#pupil").val()) Main.throwFlashMessage("#messages_place", 'Выберите студента', 'alert-danger');
        else if ($("#group_from").val() === $("#group_to").val()) Main.throwFlashMessage("#messages_place", 'Невозможно перевести в ту же группу', 'alert-danger');
        else if ($("#move_date").val().length == 0) Main.throwFlashMessage("#messages_place", 'Укажите дату перевода', 'alert-danger');
        else {
            this.lockMoveButton();
            $.ajax({
                url: "/group/process-move-pupil",
                type: 'post',
                dataType: 'json',
                data: {
                    user_id: $("#pupil").val(),
                    group_from: $("#group_from").val(),
                    group_to: $("#group_to").val(),
                    move_date: $("#move_date").val()
                },
                success: function (data) {
                    if (data.status === 'ok') {
                        Main.throwFlashMessage('#messages_place', 'Студент переведён', 'alert-success');
                        $("#pupil").val('');
                        $("#group_from").val('');
                    } else Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                    Group.unlockMoveButton();
                },
                error: function (xhr, textStatus, errorThrown) {
                    Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                    Group.unlockMoveButton();
                }
            });
        }
    },
    lockMoveButton: function () {
        $("#move_pupil_button").attr('disabled', true);
    },
    unlockMoveButton: function () {
        $("#move_pupil_button").removeAttr('disabled');
    }
};