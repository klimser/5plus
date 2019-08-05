let WelcomeLesson = {
    statusUnknown: 1,
    statusPassed: 2,
    statusMissed: 3,
    statusCanceled: 4,
    statusDenied: 5,
    changeStatusHandler: function(e, id, status) {
        return Main.changeEntityStatus('welcome-lesson', id, status, e, function(data) {
            if (data.status === 'ok') {
                WelcomeLesson.setButtons($('tr[data-key="' + data.id + '"] td:last-child'), data.id, data.state);
            }
        });
    },
    setButtons: function(e, id, status) {
        let contents = '';
        switch (status) {
            case this.statusUnknown:
                contents =
                    '<a href="#" title="Проведено" class="btn btn-primary" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusPassed + ')">' +
                        '<span class="fas fa-check"></span>' +
                    '</a>' +
                    '<a href="#" title="Отменено" class="btn btn-danger" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusCanceled + ')">' +
                        '<span class="fas fa-times"></span>' +
                    '</a>';
                break;
            case this.statusPassed:
                contents =
                    '<button class="btn btn-primary" type="button" title="В группу!" onclick="return WelcomeLesson.showMovingForm(this, ' + id + ')">' +
                    '<span class="fas fa-user-check"></span>' +
                    '</button>' +
                    '<a href="#" title="Не пришёл" class="btn btn-warning" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusMissed + ')">' +
                    '<span class="fas fa-user-slash"></span>' +
                    '</a>' +
                    '<a href="#" title="Не будет ходить" class="btn btn-danger" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusDenied + ')">' +
                    '<span class="fas fa-running"></span>' +
                    '</a>';
                break;
        }
        $(e).html('<span class="text-nowrap welcome-lesson-buttons">' + contents + '</span>');
    },
    showMovingForm: function(e, lessonId) {
        $(e).prop("disabled", true);
        $.ajax({
            url: '/welcome-lesson/propose-group',
            type: 'post',
            dataType: 'json',
            data: {id: lessonId},
            success: function(data) {
                if (data.status === "ok") {
                    $(".welcome-lesson-buttons button").prop("disabled", false);
                    let form = $("#moving-form");
                    form.find("#lesson_id").val(data.id);
                    form.find("#pupil").html(data.pupilName);
                    form.find("#start_date").html(data.lessonDate);
                    let proposals = '';
                    data.groups.forEach(function(group) {
                        proposals += '<div class="radio"><label>' +
                            '<input type="radio" name="group_proposal" value="' + group.id + '" onchange="WelcomeLesson.groupChange(this);"> ' + group.name + ' (' + group.teacherName + ')' +
                            '</label></div>';
                    });
                    form.find("#group_proposal").html(proposals);
                    $("#moving-modal").modal('show');
                } else {
                    Main.throwFlashMessage('#messages_place', "Ошибка: " + data.message, 'alert-danger');
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            }
        });
    },
    groupChange: function(e) {
        $("#other_group").prop("disabled", parseInt($(e).val()) !== 0 || !$(e).is(":checked"));
    },
    lockMovingFormButtons: function() {
        $("#moving-form").find('button').prop("disabled", true);
    },
    unlockMovingFormButtons: function() {
        $("#moving-form").find('button').prop("disabled", false);
    },
    movePupil: function(form) {
        this.lockMovingFormButtons();
        $.ajax({
            url: '/welcome-lesson/move',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize(),
            success: function(data) {
                WelcomeLesson.unlockMovingFormButtons();
                if (data.status === "ok") {
                    $("#moving-form").modal("hide");
                } else {
                    Main.throwFlashMessage('#modal_messages_place', "Ошибка: " + data.message, 'alert-danger');
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#modal_messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                WelcomeLesson.unlockMovingFormButtons();
            }
        });
    }
};
