let Group = {
    increment: 1,
    isNew: true,
    startDate: null,
    endDate: null,
    active: '',
    teacherList: [],
    teacherMap: [],
    dateRegexp: /\d{2}\.\d{2}\.\d{4}/,
    loadTeacherMap: function () {
        if (this.teacherMap.length === 0) {
            this.active = parseInt($("#group-teacher_id").val());
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
        let subjectId = parseInt($(e).val());
        $("#group-teacher_id").data("subject", subjectId);

        if (typeof this.teacherList[subjectId] === 'undefined') {
            $.ajax({
                url: '/teacher/list-json',
                type: 'get',
                dataType: 'json',
                data: {subject: subjectId},
                success: function (data) {
                    let tList = [];
                    data.teachers.forEach(function(teacher) {
                        tList.push(teacher);
                    });
                    Group.teacherList[data.subjectId] = tList;
                    Group.fillTeacherSelect(data.subjectId);
                }
            });
        } else this.fillTeacherSelect(subjectId);
    },
    fillTeacherSelect: function (subjectId) {
        if ($("#group-teacher_id").data("subject") === subjectId)
            $("#group-teacher_id").html(this.getTeachersOptions(subjectId)).removeData("subject");
    },
    getTeachersOptions: function (subjectId) {
        let list = '';
        this.teacherList[subjectId].forEach(function(teacherId) {
            list += '<option value="' + teacherId + '"' +
                (teacherId === Group.active ? ' selected' : '') + '>' + Group.teacherMap[teacherId] + '</option>';
        });
        return list;
    },
    pupilsMap: [],
    pupilsActive: [],
    loadPupilsMap: function () {
        if (this.pupilsMap.length === 0) {
            $.ajax({
                url: '/user/pupils',
                dataType: 'json',
                success: function (data) {
                    data.forEach(function(pupil) {
                        Group.pupilsMap.push(pupil);
                    });
                }
            });
        }
    },
    renderPupilForm: function () {
        let pupilSelect = '<select name="pupil[]" class="form-control chosen pupil-id"><option value=""></option>';
        this.pupilsMap.forEach(function(pupil) {
            let used = false;
            for (let j = 0; j < Group.pupilsActive.length; j++) {
                if (pupil.id === Group.pupilsActive[j]) {
                    used = true;
                    break;
                }
            }
            if (!used) pupilSelect += '<option value="' + pupil.id + '">' + pupil.name + '</option>';
        });
        pupilSelect += '</select>';

        let formHtml = '<div class="row row-pupil mt-3"><div class="col-12">' +
            '<div class="row form-group">' +
                '<div class="col-9 col-sm-10 col-md-auto">' + pupilSelect + '</div>' +
                '<div class="col-3 col-sm-2 col-md-auto">' +
                    '<button type="button" class="btn btn-outline-dark" onclick="return Group.removePupil(this);" title="Удалить">' +
                    '<span class="fas fa-user-minus"></span></button>' +
                '</div>' +
            '</div>';
        if (!this.isNew) {
            formHtml += '<div class="row form-group">' +
                '<div class="col-6 col-sm-auto form-inline align-items-start">' +
                    '<label class="mr-2 mt-2">C</label>' +
                    '<input type="text" class="form-control pupil-date-start" name="pupil_start[]" id="group-pupil-new-date-start-' + Group.increment + '" ' +
                    'required autocomplete="off" onchange="Main.handleDateRangeFrom(this);" data-target-to-closest=".row-pupil" data-target-to-selector=".pupil-date-end">' +
                '</div>';
            formHtml +=
                '<div class="col-6 col-sm-auto form-inline align-items-start">' +
                    '<label class="mr-2 mt-2">ДО</label>' +
                    '<input type="text" class="form-control pupil-date-end" name="pupil_end[]" id="group-pupil-new-date-end-' + Group.increment + '" ' +
                    'autocomplete="off" onchange="Main.handleDateRangeTo(this);" data-target-from-closest=".row-pupil" data-target-from-selector=".pupil-date-start">' +
                '</div>' +
            '</div>';
            Group.increment++;
        }
        formHtml += '</div></div>';
        $("#group_pupils").append(formHtml);
        $('#group_pupils .row-pupil:last .chosen').chosen({
            disable_search_threshold: 6,
            no_results_text: 'Студент не найден',
            placeholder_text_single: 'Выберите студента'
        });
        if (!this.isNew) {
            $('#group_pupils .row-pupil:last .pupil-date-start').datepicker({
                format: "dd.mm.yyyy",
                firstDay: 1,
                minDate: this.startDate,
                maxDate: this.endDate
            });
            $('#group_pupils .row-pupil:last .pupil-date-end').datepicker({
                format: "dd.mm.yyyy",
                firstDay: 1,
                minDate: this.startDate,
                maxDate: this.endDate
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

        if ($('input[name^="weekday"]:checked').length === 0) {
            $("#weekdays").addClass('has-error');
            validForm = false;
        } else $("#weekdays").removeClass('has-error');

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
            $(e).closest('.one_day_block').find("input.weektime").prop('disabled', false);
        } else {
            $(e).closest('.one_day_block').find("input.weektime").prop('disabled', true);
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
    },
    setEndReason: function(form, triggerHide) {
        let groupPupilId = $(form).find("input[name=group_pupil_id]").val();
        if (groupPupilId <= 0) return false;
        let pupilRow = $("#pupil_row_" + groupPupilId);
        
        let reasonIdInput = $(pupilRow).find('input[name="pupil_reason_id[' + groupPupilId + ']"]');
        if (reasonIdInput.length === 0) {
            $(pupilRow).append('<input type="hidden" name="pupil_reason_id[' + groupPupilId + ']">');
            $(pupilRow).append('<input type="hidden" name="pupil_reason_comment[' + groupPupilId + ']">');
            reasonIdInput = $(pupilRow).find('input[name="pupil_reason_id[' + groupPupilId + ']"]');
        }

        let reasonCommentInput = $(pupilRow).find('input[name="pupil_reason_comment[' + groupPupilId + ']"]');
        let reasonId = $(form).find("input[name=reason_id]:checked").val();

        $(reasonIdInput).val(reasonId);
        $(reasonCommentInput).val($(form).find("textarea[name=reason_comment]").val());
        
        return (reasonId > 0);
    },
    handlePupilEndDate: function(elem) {
       if ($(elem).val()) {
           let groupPupilId = $(elem).data('id');
           let endReasonForm = $("#end-reason-form");
           $(endReasonForm).find("input[name=group_pupil_id]").val(groupPupilId);
           $(endReasonForm).find("input[name=reason_id]").prop("checked", false);
           let commentText = "";
           let pupilRow = $("#pupil_row_" + groupPupilId);
           let reasonIdInput = $(pupilRow).find('input[name="pupil_reason_id[' + groupPupilId + ']"]');
           if (reasonIdInput.length > 0) {
               $(endReasonForm).find("input[name=reason_id][value=" + $(reasonIdInput).val() + "]").prop("checked", true);
               let reasonCommentInput = $(pupilRow).find('input[name="pupil_reason_comment[' + groupPupilId + ']"]');
               commentText = $(reasonCommentInput).val();
           }
           $(endReasonForm).find("textarea[name=reason_comment]").val(commentText);
           $("#end-reason-modal").modal('show');
       }
    }
};
