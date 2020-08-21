let MainPage = {
    subjectList: [],
    launchModal: function () {
        let targetSelect = $("select#order-subject");
        if (targetSelect) {
            targetSelect.html('');
            this.subjectList.forEach(function (subjectCategory) {
                let options = '';
                subjectCategory.subjects.forEach(function (subject) {
                    options += '<option value="' + subject.id + '">' + subject.name + '</option>';
                });
                targetSelect.append('<optgroup label="' + subjectCategory.name + '">' + options + '</optgroup>');
            });
        }
        let formBlock = $("#order_form");
        if (!$(formBlock).find(".order_form_body").hasClass("show")) grecaptcha.reset();
        $(formBlock).find(".order_form_body").collapse("show");
        $(formBlock).find(".order_form_extra").html('').collapse("hide");
        $(formBlock).find(".modal-footer").collapse("show");
        $(formBlock).modal();
    },
    completeOrder: function(form, checkCaptcha) {
        if (checkCaptcha === undefined || checkCaptcha) {
            let gToken = grecaptcha.getResponse();
            if (gToken.length === 0) return false;
        }
        $(form).find("button[type=submit]").prop("disabled", true);
        $(form).attr("pending", 1);
        $.ajax({
                url: $(form).attr('action'),
                method: 'post',
                dataType: 'json',
                data: $(form).serialize()
            })
            .done(function (data) {
                let activeForm = $("form[pending]");
                if (data.status === 'ok') {
                    let formBody = $(activeForm).find(".order_form_body");
                    let formFooter = $(activeForm).find(".modal-footer");
                    if (formBody) $(formBody).collapse("hide");
                    if (formFooter) $(formFooter).collapse("hide");
                    Main.throwFlashMessage($(activeForm).find(".order_form_extra"), 'Ваша заявка принята. Наши менеджеры свяжутся с вами в ближайшее время.', 'alert-success');
                } else {
                    Main.throwFlashMessage($(activeForm).find(".order_form_extra"), 'Не удалось отправить заявку: ' + data.errors , 'alert-danger');
                    grecaptcha.reset();
                }
                $(activeForm).find(".order_form_extra").collapse("show");
            })
            .fail(function (xhr, textStatus, errorThrown) {
                let activeForm = $("form[pending]");
                Main.throwFlashMessage($(activeForm).find(".order_form_extra"), 'Произошла ошибка при отправке заявки. Вы также можете оставить заявку по телефону.', 'alert-danger');
                $(activeForm).find(".order_form_extra").collapse("show");
            })
            .always(function() {
                let activeForm = $("form[pending]");
                $(activeForm).find("button[type=submit]").prop("disabled", false);
                $(activeForm).removeAttr('pending');
            });
        return false;
    },
    init: function() {
        $('[data-toggle="popover"]').popover();
        
        $.ajax({
            url: '/subject/list',
            success: function(data) {
                if (data.length > 0) {
                    MainPage.subjectList = data;
                }
            }
        });
    }
};
