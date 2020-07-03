let Review = {
    launchModal: function () {
        if (!$("#review_form_body").hasClass("show")) grecaptcha.reset();
        $("#review_form_body").collapse("show");
        $("#review_form_extra").html('').collapse("hide");
        $("#review_form").find(".modal-footer").collapse("show");
        $("#review_form").modal();
    },
    complete: function(form) {
        var gToken = grecaptcha.getResponse();
        if (gToken.length === 0) return false;
        $("#review_form").find("button.btn-primary").prop("disabled", true);
        $.ajax({
            url: $(form).attr('action'),
            method: 'post',
            dataType: 'json',
            data: $(form).serialize(),
            success: function (data) {
                if (data.status === 'ok') {
                    $("#review_form_body").collapse("hide");
                    $("#review_form").find(".modal-footer").collapse("hide");
                    Main.throwFlashMessage("#review_form_extra", 'Спасибо за ваш отзыв. После проверки модератором он будет размещён на сайте.', 'alert-success');
                } else {
                    Main.throwFlashMessage("#review_form_extra", 'Не удалось отправить отзыв: ' + data.errors , 'alert-danger');
                    grecaptcha.reset();
                }
                $("#review_form_extra").collapse("show");
                $("#review_form").find("button.btn-primary").prop("disabled", false);
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage("#review_form_extra", 'Произошла ошибка при отправке отзыва. Мы уже в курсе и постараемся исправить её как можно скорее.', 'alert-danger');
                $("#review_form_extra").collapse("show");
                $("#review_form").find("button.btn-primary").prop("disabled", false);
            }
        });
        return false;
    }
};
