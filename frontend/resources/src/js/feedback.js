var Feedback = {
    complete: function(form) {
        var gToken = grecaptcha.getResponse();
        if (gToken.length === 0) return false;
        $("#feedback_form_extra").html('');
        $("#feedback_form").find("button.btn-primary").prop("disabled", true);
        $.ajax({
            url: $(form).attr('action'),
            method: 'post',
            dataType: 'json',
            data: $(form).serialize(),
            success: function (data) {
                if (data.status === 'ok') {
                    $("#feedback_form_body").addClass("hidden");
                    $("#feedback_form_complete").removeClass("hidden");
                } else {
                    Main.throwFlashMessage("#feedback_form_extra", 'Не удалось отправить отзыв: ' + data.errors , 'alert-danger');
                    grecaptcha.reset();
                }
                $("#feedback_form").find("button.btn-primary").prop("disabled", false);
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage("#feedback_form_extra", 'Произошла ошибка при отправке сообщения. Мы уже в курсе и постараемся исправить её как можно скорее.', 'alert-danger');
                $("#feedback_form").find("button.btn-primary").prop("disabled", false);
            }
        });
        return false;
    },
    resetForm: function() {
        $("#feedback_form_extra").html('');
        $("#feedback_form_complete").addClass("hidden");
        $("#feedback_form_body").removeClass("hidden");
        grecaptcha.reset();
    }
};