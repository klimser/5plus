var Quiz = {
    id: null,
    editQuestion: function(questionId) {
        $.ajax({
            url: '/quiz/get-edit-question-form',
            type: 'get',
            dataType: 'json',
            data: {
                questionId: questionId
            },
            success: function(data) {
                var form = $("#edit_element_form");
                var elems = $(form).find("textarea");
                if (elems.length) {
                    elems.each(function() {
                        tinymce.get($(this).attr("id")).remove();
                    });
                }
                $(form).empty().append(data.content).collapse("show");
                eval(data.script);
            }
        });
    },
    deleteQuestion: function(questionId) {
        if (confirm("Вы уверены, что хотите удалить вопрос?")) {
            $.ajax({
                url: "/quiz/delete-question",
                type: "post",
                dataType: "json",
                data: {
                    question_id: questionId
                },
                success: function(data) {
                    if (data.status === "ok") location.reload();
                    else {
                        Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                    }
                }
            });
        }
    },
    addAnswer: function(e) {
        $(e).closest("form").find(".answers").append('<div class="row">' +
                '<div class="col-9 col-lg-10"><input class="form-control" name="wrongAnswers[]"></div>' +
                '<div class="col-3 col-lg-2">' +
                    '<button class="btn btn-outline-dark" onclick="return Quiz.removeAnswer(this);"><span class="fas fa-times"></span></button>' +
                '</div>' +
            '</div>'
        );
        return false;
    },
    removeAnswer: function(e) {
        $(e).closest('div.row').remove();
        return false;
    }
};
