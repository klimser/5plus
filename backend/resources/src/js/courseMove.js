let CourseMove = {
    init: function() {
        Main.initAutocompleteUser($("#pupil-to-move"));
        Main.loadCourses()
            .done(function(courseIds) {
                let elem = $("#group_to");
                $(elem).html('');
                courseIds.forEach(function(courseId) {
                    $(elem).append('<option value="' + courseId + '">' + Main.courseMap[courseId].name + ' (' + Main.courseMap[courseId].teacher + ')</option>');
                });
            });
    },
    loadCourses: function () {
        let studentId = $("#student-id").val();
        let courseFrom = $("#course_from");
        $(courseFrom).html('');
        if (studentId > 0 && $(courseFrom).data("student") !== studentId) {
            $(courseFrom).html('<option>загрузка...</option>');
            $(courseFrom).prop('disabled', true);
            $.ajax({
                url: '/group/list-json',
                data: {
                    pupilId: studentId
                },
                dataType: 'json'
            })
                .done(function (data) {
                    let courseFrom = $("#course_from");
                    let htmlAddon = '';
                    let activeCourseStudent = $(courseFrom).data('courseStudent');
                    data.forEach(function(courseData) {
                        let group = Main.courseMap[courseData.course_id];
                        htmlAddon += '<option value="' + courseData.id + '" data-start="' + courseData.date_start + '" ' +
                            'data-end="' + courseData.date_end + '" ' + (courseData.id === activeCourseStudent ? ' selected ' : '') + '>' +
                            group.name + '</option>';
                    });
                    $(courseFrom).html(htmlAddon);
                    $(courseFrom).data("student", $("#student-id").val());
                    $(courseFrom).change();
                })
                .fail(Main.logAndFlashAjaxError)
                .always(function() {
                    $("#group_from").prop('disabled', false);
                });
        }
    },
    selectCourse: function(e) {
        $("#course-move-id").val($(e).val());
        return this.setCourseFromDateInterval(e);
    },
    setCourseFromDateInterval: function (elem) {
        let chosenOption = $(elem).find("option:selected");
        $(elem).data('courseStudent', $(elem).val());
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
    setCourseToDateInterval: function (elem) {
        let courseId = $(elem).val();
        let dateToElem = $("#course-move-date-to");
        $(dateToElem).datepicker("option", "minDate", new Date(Main.courseMap[courseId].dateStart));
        let endDate = Main.courseMap[courseId].dateEnd;
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
    moveStudent: function () {
        let courseFromElem = $("#course_from");
        let courseToElem = $("#course_to");
        if ($(courseFromElem).val() === $(courseToElem).val()) {
            Main.throwFlashMessage("#messages_place", 'Невозможно перевести в ту же группу', 'alert-danger');
            return;
        }
        this.lockMoveButton();
        $.ajax({
            url: "/group/process-move-student",
            type: 'post',
            dataType: 'json',
            data: {
                "course-move": {
                    id: $("#course-move-id").val(),
                    course_id: $(courseToElem).val(),
                    date_from: $("#course-move-date-from").val(),
                    date_to: $("#course-move-date-to").val()
                }
            }
        })
            .done(function (data) {
                if (data.status === 'ok') {
                    Main.throwFlashMessage('#messages_place', 'Студент переведён', 'alert-success');
                    $("#move-student-form").collapse('hide');
                } else {
                    Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(CourseMove.unlockMoveButton);
    },
    lockMoveButton: function () {
        $("#move_student_button").prop('disabled', true);
    },
    unlockMoveButton: function () {
        $("#move_student_button").prop('disabled', false);
    },
};
