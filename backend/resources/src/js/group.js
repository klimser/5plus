let Group = {
    increment: 1,
    isNew: true,
    startDate: null,
    endDate: null,
    activeTeacher: 0,
    dateRegexp: /\d{2}\.\d{2}\.\d{4}/,
    loadTeacherSelect: function (e) {
        let subjectId = parseInt($(e).val());
        
        let listHtml = '';
        if (Main.teacherActiveList[subjectId] !== undefined) {
            Main.teacherActiveList[subjectId].forEach(function (teacherId) {
                listHtml += '<option value="' + teacherId + '"' +
                    (teacherId === Group.activeTeacher ? ' selected ' : '') + '>' + Main.teacherMap[teacherId].name + '</option>';
            });
        }
        
        $("#group-teacher_id").html(listHtml);
    },
    pupilsActive: [],
    renderPupilForm: function () {
        let pupilSelect = '<input type="hidden" class="autocomplete-user-id pupil-id" name="pupil[]">' +
            '<input class="autocomplete-user form-control" placeholder="начните печатать фамилию или имя" required data-role="3">';

        let formHtml = '<div class="row row-pupil mt-3"><div class="col-12">' +
            '<div class="row form-group">' +
                '<div class="col-9 col-sm-10 col-md-6 col-lg-4">' + pupilSelect + '</div>' +
                '<div class="col-3 col-sm-2 col-md-auto">' +
                    '<button type="button" class="btn btn-outline-dark" onclick="return Group.removePupil(this);" title="Удалить">' +
                    '<span class="fas fa-user-minus"></span></button>' +
                '</div>' +
            '</div>';
        if (!this.isNew) {
            formHtml += '<div class="row form-group group-pupil-block">' +
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
        formHtml += '</div></div><hr>';
        $("#group_pupils").append(formHtml);
        
        Main.initAutocompleteUser('#group_pupils .row-pupil:last .autocomplete-user');
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
        var startDateInput = $("#group-date_start");
        var endDateInput = $("#group-date_end");
        var startString = $(startDateInput).val();
        var endString = $(endDateInput).val();

        if (startString.length > 0) {
            if (!this.dateRegexp.test(startString)) {
                $(startDateInput).addClass('is-invalid');
                validForm = false;
            } else {
                startDate = new Date(parseInt(startString.substr(6)), parseInt(startString.substr(3, 2)), parseInt(startString.substr(0, 2)));
            }
        }
        if (endString.length > 0) {
            if (!this.dateRegexp.test(endString)) {
                $(endDateInput).addClass('is-invalid');
                validForm = false;
            } else {
                endDate = new Date(parseInt(endString.substr(6)), parseInt(endString.substr(3, 2)), parseInt(endString.substr(0, 2)));
            }
        }
        if (validForm) {
            if (startDate === null || (endDate !== null && endDate <= startDate)) {
                $(endDateInput).addClass('is-invalid');
                validForm = false;
            }
        }
        if (validForm) {
            $(startDateInput).removeClass('is-invalid');
            $(endDateInput).removeClass('is-invalid');
        }

        if ($('input[name^="weekday"]:checked').length === 0) {
            $('input[name^="weekday"]').addClass('is-invalid');
            validForm = false;
        } else {
            $('input[name^="weekday"]').removeClass('is-invalid');
        }

        $("input.pupil-id").each(function () {
            if ($(this).val() > 0) {
                $(this).removeClass('is-invalid');
            } else {
                $(this).addClass('is-invalid');
                validForm = false;
            }
        });
        if (validForm) {
            $(".pupil-date-start").each(function () {
                startString = $(this).val();
                endString = $(this).closest(".group-pupil-block").find('.pupil-date-end').val();
                if (!startString.length || !Group.dateRegexp.test(startString) || (endString.length && !Group.dateRegexp.test(endString))) {
                    $(this).addClass('is-invalid');
                    validForm = false;
                } else {
                    if (endString.length) {
                        var pupilStartDate = new Date(parseInt(startString.substr(6)), parseInt(startString.substr(3, 2)), parseInt(startString.substr(0, 2)));
                        var pupilEndDate = new Date(parseInt(endString.substr(6)), parseInt(endString.substr(3, 2)), parseInt(endString.substr(0, 2)));

                        if (pupilStartDate < startDate || (endDate !== null && pupilEndDate > endDate) || pupilEndDate <= pupilStartDate) {
                            $(this).addClass('is-invalid');
                            validForm = false;
                        } else $(this).removeClass('is-invalid');
                    } else $(this).removeClass('is-invalid');
                }
            });
        }

        if (!validForm) $("#form-valid").addClass('bg-danger').text('Исправьте ошибки в форме');
        else $("#form-valid").removeClass('bg-danger').text('');

        return validForm;
    },
    toggleWeekday: function (e) {
        $(e).closest('.one_day_block').find("input.weektime").prop('disabled', !$(e).is(':checked'));
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
