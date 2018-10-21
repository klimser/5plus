var Review = {
    toggleReview: function(e) {
        var item = $(e).closest(".review-item");
        var reviewBody = $(item).find(".review-body");
        if ($(reviewBody).hasClass("review-teaser")) {
            $(reviewBody).removeClass("review-teaser");
            $(item).find(".read-more-block").html('<a href="#" onclick="return Review.toggleReview(this);"><span class="glyphicon glyphicon-chevron-up"></span> <span class="link-body">Свернуть отзыв</span></a>');
        } else {
            $(reviewBody).addClass("review-teaser");
            $(item).find(".read-more-block").html('<a href="#" onclick="return Review.toggleReview(this);"><span class="glyphicon glyphicon-chevron-down"></span> <span class="link-body">Читать весь отзыв</span></a>');
        }
        return false;
    },
    launchModal: function () {
        if ($("#review_form_body").hasClass("hidden")) {
            grecaptcha.reset();
            $("#review_form_body").removeClass("hidden");
        }
        $("#review_form_extra").html('').addClass("hidden");
        $("#review_form").find(".modal-footer").removeClass("hidden");
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
                    $("#review_form_body").addClass("hidden");
                    $("#review_form").find(".modal-footer").addClass("hidden");
                    Main.throwFlashMessage("#review_form_extra", 'Спасибо за ваш отзыв. После проверки модератором он будет размещён на сайте.', 'alert-success');
                } else {
                    Main.throwFlashMessage("#review_form_extra", 'Не удалось отправить отзыв: ' + data.errors , 'alert-danger');
                    grecaptcha.reset();
                }
                $("#review_form_extra").removeClass("hidden");
                $("#review_form").find("button.btn-primary").prop("disabled", false);
            },
            error: function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage("#review_form_extra", 'Произошла ошибка при отправке отзыва. Мы уже в курсе и постараемся исправить её как можно скорее.', 'alert-danger');
                $("#review_form_extra").removeClass("hidden");
                $("#review_form").find("button.btn-primary").prop("disabled", false);
            }
        });
        return false;
    },
    init: function() {
        $(".review-item").each(function() {
            var reviewBody = $(this).find(".review-body");
            if ($(reviewBody).height() > 78) {
                $(reviewBody).addClass("review-teaser");
                $(this).find(".read-more-block").html('<a href="#" onclick="return Review.toggleReview(this);"><span class="glyphicon glyphicon-chevron-down"></span> <span class="link-body">Читать весь отзыв</span></a>');
            }
        });
    }
};