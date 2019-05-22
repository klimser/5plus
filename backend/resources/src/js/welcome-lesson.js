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
                    '<a href="#" title="Проведено" onclick="return WelcomeLesson.changeStatusHandler(this, ' + id + ', ' + WelcomeLesson.statusPassed + ')">' +
                    '<span class="fas fa-user-check"></span>' +
                    '</a>' +
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
    }
};
