var Teacher = {
    subjects: [],
    activeSubjects: [],
    renderSubjectForm: function(subjectId) {
        var formHtml = '<div class="row form-group"><div class="col-xs-10 col-md-11"><select name="subject[]" class="form-control">';
        for (var i = 0; i < this.subjects.length; i++) {
            var used = false;
            for (var j = 0; j < this.activeSubjects.length; j++) if (this.subjects[i].id !== subjectId && this.subjects[i].id === this.activeSubjects[j]) {used = true; break;}
            if (!used) formHtml += '<option value="' + this.subjects[i].id + '" ' + (subjectId === this.subjects[i].id ? 'selected' : '') + '>' + this.subjects[i].name + '</option>';
        }
        formHtml += '</select></div><div class="col-xs-2 col-md-1"><button class="btn btn-default" onclick="return Teacher.removeSubject(this);"><span class="glyphicon glyphicon-remove"></span></button></div></div>';
        $("#teacher_subjects").append(formHtml);
        return false;
    },
    renderExisted: function() {
        for (var i = 0; i < this.activeSubjects.length; i++) {
            this.renderSubjectForm(this.activeSubjects[i]);
        }
    },
    removeSubject: function(e) {
        if (confirm('Вы уверены?')) {
            $(e).closest("div.row").remove();
        }
        return false;
    }
};