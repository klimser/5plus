let Course = {
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
                    (teacherId === Course.activeTeacher ? ' selected ' : '') + '>' + Main.teacherMap[teacherId].name + '</option>';
            });
        }
        
        $("#courseconfig-teacher_id").html(listHtml);
    },
    studentsActive: [],
    renderStudentForm: function () {
        let studentSelect = '<input type="hidden" class="autocomplete-user-id student-id" name="student[]">' +
            '<input class="autocomplete-user form-control" placeholder="начните печатать фамилию или имя" required data-role="3">';

        let formHtml = '<div class="row row-student mt-3"><div class="col-12">' +
            '<div class="row form-group">' +
                '<div class="col-9 col-sm-10 col-md-6 col-lg-4">' + studentSelect + '</div>' +
                '<div class="col-3 col-sm-2 col-md-auto">' +
                    '<button type="button" class="btn btn-outline-dark" onclick="return Course.removeStudent(this);" title="Удалить">' +
                    '<span class="fas fa-user-minus"></span></button>' +
                '</div>' +
            '</div>';
        if (!this.isNew) {
            formHtml += '<div class="row form-group course-student-block">' +
                '<div class="col-6 col-sm-auto form-inline align-items-start">' +
                    '<label class="mr-2 mt-2">C</label>' +
                    '<input type="text" class="form-control student-date-start" name="student_start[]" id="course-student-new-date-start-' + Course.increment + '" ' +
                    'required autocomplete="off" onchange="Main.handleDateRangeFrom(this);" data-target-to-closest=".row-student" data-target-to-selector=".student-date-end">' +
                '</div>';
            formHtml +=
                '<div class="col-6 col-sm-auto form-inline align-items-start">' +
                    '<label class="mr-2 mt-2">ДО</label>' +
                    '<input type="text" class="form-control student-date-end" name="student_end[]" id="course-student-new-date-end-' + Course.increment + '" ' +
                    'autocomplete="off" onchange="Main.handleDateRangeTo(this);" data-target-from-closest=".row-student" data-target-from-selector=".student-date-start">' +
                '</div>' +
            '</div>';
            Course.increment++;
        }
        formHtml += '</div></div><hr>';
        $("#course_students").append(formHtml);
        
        Main.initAutocompleteUser('#course_students .row-student:last .autocomplete-user');
        if (!this.isNew) {
            $('#course_students .row-student:last .student-date-start').datepicker({
                format: "dd.mm.yyyy",
                firstDay: 1,
                minDate: this.startDate,
                maxDate: this.endDate
            });
            $('#course_students .row-student:last .student-date-end').datepicker({
                format: "dd.mm.yyyy",
                firstDay: 1,
                minDate: this.startDate,
                maxDate: this.endDate
            });
        }
        return false;
    },
    removeStudent: function (e) {
        if (confirm('Вы уверены?')) {
            $(e).closest("div.row-student").remove();
        }
        return false;
    },
    submitForm: function () {
        let validForm = true;
        let startDate = null, endDate = null;
        let startDateInput = $("#course-date_start");
        let endDateInput = $("#course-date_end");
        let startString = $(startDateInput).val();
        let endString = $(endDateInput).val();

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

        let teacherRateInput = $('#courseconfig-teacher_rate');
        let teacherLessonPayInput = $('#courseconfig-teacher_lesson_pay');
        if ($(teacherRateInput).is(':not([disabled])')) {
            if (($(teacherRateInput).val().length === 0 && $(teacherLessonPayInput).val().length === 0)
                || ($(teacherRateInput).val().length !== 0 && $(teacherLessonPayInput).val().length !== 0)) {
                $(teacherRateInput).addClass('is-invalid');
                $(teacherLessonPayInput).addClass('is-invalid');
                validForm = false;
            } else {
                $(teacherRateInput).removeClass('is-invalid');
                $(teacherLessonPayInput).removeClass('is-invalid');
            }
        }

        let timeInputs = $('input[name^="weektime"]:not([disabled])');
        if (timeInputs.length > 0) {
            let scheduleValid = false;
            $(timeInputs).each(function () {
                if ($(this).val().length > 0) {
                    scheduleValid = true;
                }
            });

            if (scheduleValid) {
                $(timeInputs).removeClass('is-invalid');
            } else {
                $(timeInputs).addClass('is-invalid');
                validForm = false;
            }
        }

        $("input.student-id").each(function () {
            if ($(this).val() > 0) {
                $(this).removeClass('is-invalid');
            } else {
                $(this).addClass('is-invalid');
                validForm = false;
            }
        });
        if (validForm) {
            $(".student-date-start").each(function () {
                startString = $(this).val();
                endString = $(this).closest(".course-student-block").find('.student-date-end').val();
                if (!startString.length || !Course.dateRegexp.test(startString) || (endString.length && !Course.dateRegexp.test(endString))) {
                    $(this).addClass('is-invalid');
                    validForm = false;
                } else {
                    if (endString.length) {
                        let studentStartDate = new Date(parseInt(startString.substr(6)), parseInt(startString.substr(3, 2)), parseInt(startString.substr(0, 2)));
                        let studentEndDate = new Date(parseInt(endString.substr(6)), parseInt(endString.substr(3, 2)), parseInt(endString.substr(0, 2)));

                        if (studentStartDate < startDate || (endDate !== null && studentEndDate > endDate) || studentEndDate <= studentStartDate) {
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
        let courseStudentId = $(form).find("input[name=course_student_id]").val();
        if (courseStudentId <= 0) return false;
        let studentRow = $("#student_row_" + courseStudentId);
        
        let reasonIdInput = $(studentRow).find('input[name="student_reason_id[' + courseStudentId + ']"]');
        if (reasonIdInput.length === 0) {
            $(studentRow).append('<input type="hidden" name="student_reason_id[' + courseStudentId + ']">');
            $(studentRow).append('<input type="hidden" name="student_reason_comment[' + courseStudentId + ']">');
            reasonIdInput = $(studentRow).find('input[name="student_reason_id[' + courseStudentId + ']"]');
        }

        let reasonCommentInput = $(studentRow).find('input[name="student_reason_comment[' + courseStudentId + ']"]');
        let reasonId = $(form).find("input[name=reason_id]:checked").val();

        $(reasonIdInput).val(reasonId);
        $(reasonCommentInput).val($(form).find("textarea[name=reason_comment]").val());
        
        return (reasonId > 0);
    },
    handleStudentEndDate: function(elem) {
       if ($(elem).val()) {
           let courseStudentId = $(elem).data('id');
           let endReasonForm = $("#end-reason-form");
           $(endReasonForm).find("input[name=course_student_id]").val(courseStudentId);
           $(endReasonForm).find("input[name=reason_id]").prop("checked", false);
           let commentText = "";
           let studentRow = $("#student_row_" + courseStudentId);
           let reasonIdInput = $(studentRow).find('input[name="student_reason_id[' + courseStudentId + ']"]');
           if (reasonIdInput.length > 0) {
               $(endReasonForm).find("input[name=reason_id][value=" + $(reasonIdInput).val() + "]").prop("checked", true);
               let reasonCommentInput = $(studentRow).find('input[name="student_reason_comment[' + courseStudentId + ']"]');
               commentText = $(reasonCommentInput).val();
           }
           $(endReasonForm).find("textarea[name=reason_comment]").val(commentText);
           $("#end-reason-modal").modal('show');
       }
    },
    toggleNoteUpdate: function(e) {
        $(e).parent().append('<form onsubmit="return Course.updateNote(this);">' +
            '<input type="hidden" name="course_id" value="' + $(e).data('courseId') + '">' +
            '<div class="input-group">' +
            '<input name="note" class="form-control" placeholder="Тема" title="Тема" required>' +
            '<div class="input-group-append">' +
            '<button class="btn btn-primary">OK</button>' +
            '</div></div></form>');
        $(e).collapse('hide');
    },
    updateNote: function(form) {
        $(form).find('button').prop('disabled', true);
        $.ajax({
            'url': '/course/note-add',
            'type': 'post',
            'dataType': 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    window.location.reload(true);
                } else {
                    Main.throwFlashMessage('#messages_place', data.message, 'alert-danger');
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(function () {
                $(form).find('button').prop('disabled', false);
            });
        return false;
    },
    addConfig: function(e) {
        let formContainer = $("#config-new");
        $(formContainer).find('input,select,textarea').prop('disabled', $(formContainer).hasClass('show'));
    }
};
