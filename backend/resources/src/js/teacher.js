let Teacher = {
    subjectMap: {},
    allSubjectIds: [],
    activeSubjectIds: [],
    init: function() {
        $.getJSON('/ajax-info/subjects')
            .done(function (data) {
                data.forEach(function (subject) {
                    Teacher.subjectMap[subject.id] = subject;
                    Teacher.allSubjectIds.push(subject.id);
                });
            })
            .fail(Main.logAndFlashAjaxError);
    },
    renderSubjectForm: function() {
        let formHtml = '<div class="row form-group"><div class="col-10 col-md-11">';
        formHtml += '<select name="subject[]" class="form-control">';
        this.allSubjectIds.forEach(function (subjectId) {
            if (!Teacher.activeSubjectIds.includes(subjectId)) {
                formHtml += '<option value="' + subjectId + '">' + Teacher.subjectMap[subjectId].name + '</option>';
            }
        });
        formHtml += '</select>';
        formHtml += '</div><div class="col-2 col-md-1"><button class="btn btn-outline-dark" onclick="return Teacher.removeSubject(this);"><span class="fas fa-times"></span></button></div></div>';
        $("#teacher_subjects").append(formHtml);
        return false;
    },
    removeSubject: function(e) {
        if (confirm('Вы уверены?')) {
            $(e).closest("div.row").remove();
        }
        return false;
    }
};
