let Payment = {
    users: {},
    user: null,
    selectStudent: function(e) {
        this.user = $(e).data("student");
        $("#user_select").find("button.student-button").removeClass('btn-primary').addClass('btn-outline-dark');
        $(e).addClass('btn-primary').removeClass('btn-outline-dark');
        this.renderCourseSelect();
    },
    renderCourseSelect: function() {
        if (this.user !== null && this.users.hasOwnProperty(this.user)) {
            let htmlData = '';
            this.users[this.user].courses.forEach(function(course) {
                let addonClass = 'outline-dark';
                let addonText = '';
                if (course.debt > 0) {
                    addonClass = 'danger';
                    addonText = 'задолженность ' + course.debt + ' сум';
                } else if (course.paid.length > 0) {
                    addonText = 'оплачено до ' + course.paid;
                }
                htmlData += '<button class="mb-3 btn btn-lg btn-' + addonClass + '" type="button" data-id="' + course.id + '" onclick="Payment.toggleCourse(this);">' +
                    course.name +
                    (addonText.length > 0 ? '<br><small>' + addonText + '</small>' : '') +
                    '</button><div id="payment-' + course.id + '" class="course-payments collapse" data-courseid="' + course.id + '" data-coursename="' + course.name + '"><div class="row">';
                if (course.debt > 0) {
                    htmlData += '<div class="col-12 col-md-auto mb-2"><button class="btn btn-primary btn-block" data-sum="' + course.debt + '" onclick="Payment.selectSum(this);">' +
                        'Погасить задолженность ' + course.debt + ' сум' +
                        '</button></div>';
                }
                htmlData += '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="' + course.priceLesson + '" data-limit="' + course.priceDiscountLimit + '" onclick="Payment.selectSum(this);">' +
                    'за 1 занятие ' + course.priceLesson + ' сум' +
                    '</button></div>' +

                    '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="' + course.priceMonth + '" data-limit="' + course.priceDiscountLimit + '" onclick="Payment.selectSum(this);">' +
                    'за 1 месяц ' + course.priceMonth + ' сум' +
                    '</button></div>' +

                    '<div class="col-12 col-md-auto mb-2"><button class="btn btn-secondary btn-block" data-sum="none" data-limit="' + course.priceDiscountLimit + '" onclick="Payment.selectSum(this);">другая сумма</button></div>' +
                    '</div></div><hr>';
            });

            $("#course_select").html(htmlData);
            if (this.users[this.user].courses.length === 1) {
                $("#course_select").find('button').get(0).click();
            }
        }
    },
    toggleCourse: function(e) {
        $(".course-payments").collapse("hide");
        $("#payment-" + $(e).data("id")).collapse("show");
    },
    selectSum: function(e) {
        let amountInput = $("#amount");
        let sum = $(e).data("sum");
        $(amountInput).data('discountLimit', $(e).data("limit"));
        if (sum === 'none') {
            $(amountInput).val(0).prop('disabled', false);
        } else {
            $(amountInput).val(parseInt(sum)).prop('disabled', true);
        }

        $("#student").data("val", this.user).val(this.users[this.user].name);
        $("#course").data("val", $(e).closest(".course-payments").data("courseid")).val($(e).closest(".course-payments").data("coursename"));
        $("#payment_form").modal();
        Payment.checkAmount(amountInput);
    },
    lockPayButton: function() {
        $(".pay_button").prop("disabled", true);
        Main.throwFlashMessage('#message_board', "Подготовка к оплате. Пожалуйста, подождите...", 'alert-info');
    },
    unlockPayButton: function() {
        $(".pay_button").prop("disabled", false);
    },
    completePayment: function(button) {
        let form = $(button).closest("form");
        if (!$("#student").data("val") || !$("#course").data("val") || $("#amount").val() < 1000 || ($(form).find('#giftcard-email').length > 0 && !$("#agreement").is(":checked"))) return false;

        this.lockPayButton();
        $.ajax({
            url: $(form).attr('action'),
            type: 'post',
            dataType: 'json',
            data: {
                pupil: $("#student").data("val"),
                group: $("#course").data("val"),
                amount: $("#amount").val(),
                method: $(button).data("payment")
            },
        })
            .done(function(data) {
                if (data.status === 'error') {
                    Main.throwFlashMessage('#message_board', "Ошибка: " + data.message, 'alert-danger');
                } else {
                    location.assign(data.redirectUrl);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.throwFlashMessage('#message_board', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            })
            .always(Payment.unlockPayButton);

        return false;
    },
    completeNewPayment: function(button) {
        let form = $(button).closest("form");
        if (!form.get(0).reportValidity()) {
            return false;
        }
        let formData = $(form).serialize();
        this.lockPayButton();
        $.ajax({
                url: $(form).attr('action'),
                type: 'post',
                dataType: 'json',
                data: formData + "&method=" + $(button).data("payment"),
            })
            .done(function(data) {
                if (data.status === 'error') {
                    Main.throwFlashMessage('#message_board', "Ошибка: " + data.message, 'alert-danger');
                } else {
                    location.assign(data.redirectUrl);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.throwFlashMessage('#message_board', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            })
            .always(Payment.unlockPayButton);

        return false;
    },
    checkAmount: function(e) {
        let sum = parseInt($(e).val());
        if (sum > 0 && sum < $(e).data('discountLimit')) {
            $(e).parent().find("#amount-notice").collapse('show');
        } else {
            $(e).parent().find("#amount-notice").collapse('hide');
        }
    }
};
