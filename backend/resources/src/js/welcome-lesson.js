let WelcomeLesson = {
    statusUnknown: 1,
    statusPassed: 2,
    statusMissed: 3,
    statusCanceled: 4,
    statusDenied: 5,
    changeStatusHandler: function(e, id, status) {
        return Main.changeEntityStatus('welcome-lesson', id, status, e, function(data) {
            if (data.status === 'ok') {
                WelcomeLesson.setButtons($('tr[data-key="' + data.id + '"] td:last-child'), data.id, data.newValue);
            }
        });
    },
    setButtons: function(e, id, status) {
        switch (status) {
            case this.statusUnknown:
                $(e).html(
                    '<a href="#" title="Проведено" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusPassed + ')">' +
                        '<span class="fas fa-check"></span>' +
                    '</a>' +
                    '<a href="#" title="Отменено" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusCanceled + ')">' +
                        '<span class="fas fa-times"></span>' +
                    '</a>'
                );
                break;
            case this.statusPassed:
                $(e).html(
                    '<button class="btn btn-primary" type="button" title="В группу!" onclick="return WelcomeLesson.showMovingForm(this, ' + id + ')">' +
                    '<span class="fas fa-user-check"></span>' +
                    '</button>' +
                    '<a href="#" title="Не пришёл" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusMissed + ')">' +
                    '<span class="fas fa-user-slash"></span>' +
                    '</a>' +
                    '<a href="#" title="Не будет ходить" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusDenied + ')">' +
                    '<span class="fas fa-running"></span>' +
                    '</a>'
                );
                break;
            default:
                $(e).html('');
                break;
        }
    },
    showMovingForm: function(e, lessonId) {
        $(e).prop("disabled", true);
        $.ajax({
            url: '/welcome-lesson/propose-group',
            type: 'post',
            dataType: 'json',
            data: {id: lessonId},
            success: function(data) {
                let form = $("#moving-form");
                form.find("#pupil").html(data.pupilName);
                form.find("#start_date").html(data.lessonDate);
                let proposals = '';
                data.proposals.forEach(function(group) {
                    proposals += '<input type="radio" name="group_proposal" value="' + group.id + '"> ' + group.name + ' (' + group.teacherName + ')<br>';
                });
                form.find("#group_proposal").html(proposals);
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            }
        });
    },
    groupChange: function(e) {
        $("#other_group").prop("disabled", !$(e).is(":checked"));
    }
};
