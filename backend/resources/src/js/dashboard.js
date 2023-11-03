let Dashboard = {
    find: function(form) {
        let elem = $(form).find(".search");
        $("#search-form button").prop("disabled", true);
        $.get("/dashboard/find", {value: $(elem).val()}, null, 'html')
            .done(function(content) {
                let resultContainer = $("#result");
                $(resultContainer).html(content);
                let giftCardForm = $(resultContainer).find("#gift-card-form");
                if (giftCardForm.length > 0) {
                    Dashboard.prepareGiftCardForm(giftCardForm);
                }
            })
            .fail(Main.logAndFlashAjaxError)
            .always(function() {
                $("#search-form button").prop("disabled", false);
            });
    },
    clearInput: function(e) {
        $(e).closest(".input-group").find(".search").val("").focus();
    },
    showContractForm: function(e) {
        let form = $("#contract-form");
        let data = $(e).data();
        Object.getOwnPropertyNames(data).forEach(function (key) {
            let elem = $(form).find("#contract-" + key);
            if (elem.length > 0) {
                $(elem).val(data[key]);
            }
        });
        let studentExistsBlock = $(form).find("#contract-student-exists");
        let studentNewBlock = $(form).find("#contract-student-new");
        $(form).find("#contract-createDate").prop("disabled", data.courseStudent > 0);
        if (data.courseStudent > 0) {
            $(studentExistsBlock).collapse('show');
            $(studentNewBlock).collapse("hide");
        } else {
            $(studentExistsBlock).collapse("hide");
            $(studentNewBlock).collapse("show");
            let datepickerOptions = Main.datepickerDefaultSettings;
            datepickerOptions.minDate = data.courseDateStart;
            $(studentNewBlock).find(".datepicker").datepicker(datepickerOptions);
        }
        $('#modal-contract').modal("show");
    },
    completeContract: function(form) {
        Money.completeContract(form)
            .done(function(data) {
                $("#modal-contract").modal("hide");
                if (data.status === 'ok') {
                    $("#search-form").submit();
                }
            });
    },
    prepareGiftCardForm: function(form) {
        Main.initPhoneFormatted();
        $(form).find(".datepicker").datepicker(Main.datepickerDefaultSettings);
        let courseSelect = $(form).find("#new-course");
        Main.loadCourses()
            .done(function(courseIds) {
                let courseBlackList = [];
                $(form).find(".gift-card-existing-course").each(function(){
                    courseBlackList.push($(this).data("course"));
                });
                $(courseSelect).html('');
                courseIds.forEach(function(courseId) {
                    if (courseBlackList.indexOf(courseId) === -1) {
                        courseSelect.append('<option value="' + courseId + '">' + Main.courseMap[courseId].name + ' (' + Main.courseMap[courseId].teacher + ')</option>');
                    }
                });
                $(courseSelect).change();
            })
            .fail(Main.logAndFlashAjaxError);
    },
    setGiftCourse: function(e) {
        $(".gift-card-existing-course").removeClass("btn-primary");
        let existingElem = $("#existing_course_id");
        let newCourseElem = $("#new-course");
        let newCourseDateElem = $("#new-course-date");
        if (parseInt(existingElem.val()) === $(e).data("course")) {
            existingElem.val('');
            $(newCourseElem).prop("disabled", false);
            $(newCourseDateElem).prop("disabled", false);
        } else {
            existingElem.val($(e).data("course"));
            $(e).addClass("btn-primary");
            $(newCourseElem).prop("disabled", true);
            $(newCourseDateElem).prop("disabled", true);
        }
    },
    selectGiftCourse: function(e) {
        let course = Main.courseMap[$(e).val()];
        let limitDate = this.studentLimitDate !== null && this.studentLimitDate > course.dateStart ? this.studentLimitDate : course.dateStart;
        $(e).closest("#gift-card-form").find(".datepicker").datepicker("option", "minDate", new Date(limitDate));
    },
    completeGiftCard: function(form) {
        Money.completeGiftCard(form)
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#search-form").submit();
                }
            });
    },
    toggleChildren: function(e) {
        let childrenBlock = $(e).closest(".result-parent").find(".children-list");
        $(childrenBlock).collapse("toggle");
    },
    refreshStudentInfo: function(e) {
        let tabId = $(e).closest(".student-info").find(".user-view-tabs .tab-pane.active").attr("id");
        tabId = tabId.split("-");
        return this.toggleStudentInfo(e, true, tabId[0]);
    },
    toggleStudentInfo: function(e, forceReload, activeTab) {
        let childrenInfoBlock = $(e).closest(".result-student").find(".student-info");
        $(childrenInfoBlock).collapse(forceReload ? 'show' : 'toggle');

        if ($(childrenInfoBlock).html().length === 0 || forceReload === true) {
            $(childrenInfoBlock).html('<div class="loading-box"></div>');
            return $.ajax({
                url: '/user/view',
                type: 'get',
                dataType: 'html',
                data: {id: $(childrenInfoBlock).data("id"), tab: activeTab}
            })
                .done(function(data) {
                    let htmlAddon = '<button type="button" class="btn btn-outline-secondary float-right" onclick="Dashboard.refreshStudentInfo(this);"><span class="fas fa-sync"></span></button>';
                    $(childrenInfoBlock).html(htmlAddon + data);
                    Main.initPhoneFormatted();
                    Main.initTooltip($(childrenInfoBlock).find('[data-toggle="tooltip"]'));
                    User.init(true)
                        .fail(Main.jumpToTop);
                    WelcomeLesson.init($(childrenInfoBlock).find('.welcome-table'))
                        .fail(Main.jumpToTop);
                })
                .fail(Main.logAndFlashAjaxError)
                .fail(Main.jumpToTop);
        }
    },
    showEditForm: function(prefix, e) {
        let container = $(e).closest("." + prefix + "-info-block");
        $(container).find("." + prefix + "-view-block").collapse("hide");
        $(container).find("." + prefix + "-edit-block").collapse("show")
            .find("input, textarea").prop("disabled", false);
    },
    changeParentType: function(e, inputName) {
        let block = $(e).closest(".parent-edit-block");
        $(block).find(".parent-edit-option").each(function() {
            $(this).collapse("hide");
            $(this).find("input").prop("disabled", true);
        });
        if (inputName === undefined) {
            inputName = 'parent_type';
        }
        let checkedVal = $(block).find('input[name="' + inputName + '"]:checked').val();
        let activeBlock = $(block).find(".parent-edit-" + checkedVal);
        if (activeBlock.length > 0) {
            $(activeBlock).collapse("show");
            $(activeBlock).find("input").prop("disabled", false);
        }
    },
    saveStudent: function(form) {
        this.lockStudentInfoButtons(form);
        let messagesPlace = $(form).find(".user-view-messages-place");
        $.ajax({
            url: '/user/update-ajax',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    let container = $(form).closest('.student-info');
                    Dashboard.toggleStudentInfo(form, true)
                        .done(function() {
                            let messagesPlace = $(container).find(".user-view-messages-place");
                            data.infoFlash.forEach(function(message) {
                                Main.throwFlashMessage(messagesPlace, message, 'alert-info', true);
                            });
                        });
                } else {
                    if (data.errors) {
                        data.errors.forEach(function (error) {
                            Main.throwFlashMessage(messagesPlace, error, 'alert-danger', true);
                        });
                    } else {
                        Main.throwFlashMessage(messagesPlace, data.message, 'alert-danger');
                    }
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, messagesPlace);
            })
            .always(function() {
                Dashboard.unlockStudentInfoButtons(form);
            });
    },
    lockStudentInfoButtons: function(container)
    {
        $(container).find("button").prop("disabled", true);
    },
    unlockStudentInfoButtons: function(container)
    {
        $(container).find("button").prop("disabled", false);
    },
    printWelcomeLessonInfo: function(button) {
        let welcomeTable = $(button).closest('.student-info').find('.welcome-table');
        let ids = [];
        $(welcomeTable).find('tr.welcome-row').each(function() {
            if ($(this).data("status") === WelcomeLesson.statusUnknown) {
                ids.push($(this).data("key"));
            }
        });
        if (ids.length > 0) {
            let href = '/welcome-lesson/print';
            let first = true;
            ids.forEach(function (id) {
                href += (first ? '?' : '&') + 'id[]=' + id;
                first = false;
            });
            window.open(href);
        }
    },
    showMoneyIncomeForm: function(e) {
        let courseId = $(e).data('course');
        let course = Main.courseMap[courseId];
        
        $('#income-messages-place').html('');
        let form = $("#income-form");
        $(form).find("#income-user-id").val($(e).data("user"));
        $(form).find("#income-course-id").val(courseId);
        $(form).find("#income-student-name").val($(e).closest(".result-student").find(".student-name").text());
        $(form).find(".income-amount").val(0).data('discountLimit', course.price12Lesson);
        $(form).find("#amount-notice").collapse('hide');
        $(form).find("#payment_comment").val('');
        $(form).find("#income-course-name").val(course.name);
        let amountHelpersBlock = $(form).find(".amount-helper-buttons");
        $(amountHelpersBlock).find(".price-lesson").data('price', course.priceLesson);
        $(amountHelpersBlock).find(".price-month").data('price', course.priceMonth);
        $("#modal-income").modal("show");
    },
    setAmount: function(e) {
        let incomeInput = $(e).closest(".form-group").find(".income-amount");
        $(incomeInput).val($(e).data('price'));
        this.checkAmount(incomeInput);
    },
    checkAmount: function(e) {
        let sum = parseInt($(e).val());
        if (sum > 0 && sum < $(e).data('discountLimit')) {
            $(e).parent().find("#amount-notice").collapse('show');
        } else {
            $(e).parent().find("#amount-notice").collapse('hide');
        }
    },
    completeIncome: function(form) {
        this.lockIncomeButton();
        return $.ajax({
                url: '/money/process-income',
                type: 'post',
                dataType: 'json',
                data: $(form).serialize()
            })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-income").modal("hide");
                    let container = $('#user-view-' + data.userId).closest(".student-info");
                    Dashboard.toggleStudentInfo(container, true, 'course')
                        .done(function() {
                            let messagesPlace = $(container).find('.user-view-messages-place');
                            Main.throwFlashMessage(messagesPlace, 'Внесение денег успешно зафиксировано, номер транзакции - ' + data.paymentId, 'alert-success');
                            Main.throwFlashMessage(messagesPlace, 'Договор зарегистрирован. <a target="_blank" href="' + data.contractLink + '">Распечатать</a>', 'alert-info', true);
                        });
                } else {
                    Main.throwFlashMessage('#income-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#income-messages-place');
            })
            .always(Dashboard.unlockIncomeButton);
    },
    lockIncomeButton: function() {
        $("#income-button").prop('disabled', true);
    },
    unlockIncomeButton: function() {
        $("#income-button").prop('disabled', false);
    },
    showMoneyDebtForm: function(e, isRefund = false) {
        let courseId = $(e).data('course');
        let course = Main.courseMap[courseId];

        $('#debt-messages-place').html('');
        let form = $("#debt-form");
        $(form).find("#debt-user-id").val($(e).data("user"));
        $(form).find("#debt-course-id").val(courseId);
        $(form).find("#debt-student-name").val($(e).closest(".result-student").find(".student-name").text());
        $(form).find("#debt_comment").val('');
        if (isRefund) {
            $(form).find("#debt-amount").val(parseInt($(e).data('amount').replace(/ /g, '')));
            $(form).find("#debt-amount").prop("readonly", true);
            $(form).find("#debt-refund").val(1);
        } else {
            $(form).find("#debt-amount").val(0);
            $(form).find("#debt-amount").prop("readonly", false);
            $(form).find("#debt-refund").val(0);
        }
        $(form).find("#debt-course-name").val(course.name);
        $("#modal-debt .modal-title").text(isRefund ? 'Возврат средств' : 'Добавить долг');
        $("#modal-debt").modal("show");
    },
    completeDebt: function(form) {
        this.lockDebtButton();
        return $.ajax({
            url: '/money/process-debt',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-debt").modal("hide");
                    let container = $('#user-view-' + data.userId).closest(".student-info");
                    Dashboard.toggleStudentInfo(container, true, 'course')
                        .done(function() {
                            let messagesPlace = $(container).find('.user-view-messages-place');
                            Main.throwFlashMessage(messagesPlace, data.refund > 0 ? 'Возврат зарегистрирован' : 'Долг добавлен', 'alert-success');
                        });
                } else {
                    Main.throwFlashMessage('#debt-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#debt-messages-place');
            })
            .always(Dashboard.unlockDebtButton);
    },
    lockDebtButton: function() {
        $("#debt-button").prop('disabled', true);
    },
    unlockDebtButton: function() {
        $("#debt-button").prop('disabled', false);
    },
    showNewContractForm: function(e) {
        let courseId = $(e).data('course');
        let course = Main.courseMap[courseId];
        
        $('#new-contract-messages-place').html('');
        let form = $("#new-contract-form");
        $(form).find("#new-contract-user-id").val($(e).data("user"));
        $(form).find("#new-contract-course-id").val(courseId);
        $(form).find("#new-contract-student-name").val($(e).closest(".result-student").find(".student-name").text());
        $(form).find(".income-amount").val(0);
        $(form).find("#new-contract-course-name").val(course.name);
        let amountHelpersBlock = $(form).find(".amount-helper-buttons");
        $(amountHelpersBlock).find(".price-lesson").data('price', course.priceLesson);
        $(amountHelpersBlock).find(".price-month").data('price', course.priceMonth);
        $("#modal-new-contract").modal("show");
    },
    issueNewContract: function(form) {
        this.lockNewContractButton();
        return $.ajax({
            url: '/contract/create-ajax',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-new-contract").modal("hide");
                    let messagesPlace = $('#user-view-' + data.userId).find('.user-view-messages-place');
                    Main.throwFlashMessage(messagesPlace, 'Договор зарегистрирован. <a target="_blank" href="' + data.contractLink + '">Распечатать</a>', 'alert-info');
                } else {
                    Main.throwFlashMessage('#new-contract-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#new-contract-messages-place');
            })
            .always(Dashboard.unlockNewContractButton);
    },
    lockNewContractButton: function() {
        $("#new-contract-button").prop('disabled', true);
    },
    unlockNewContractButton: function() {
        $("#new-contract-button").prop('disabled', false);
    },
    showMoveStudentForm: function(e) {
        let courseStudentId = $(e).data('id');
        let courseId = parseInt($(e).data('course'));
        let course = Main.courseMap[courseId];
        $('#course-move-messages-place').html('');
        let form = $("#course-move-form");
        $(form).find("#course-move-id").val(courseStudentId);
        $(form).find("#course-move-course").val(course.name);
        $(form).find("#course-move-student-name").val($(e).closest(".result-student").find(".student-name").text());
        let optionsHtml = '';
        Main.courseActiveList.forEach(function(id) {
            if (id !== courseId) {
                optionsHtml += '<option value="' + id + '">' + Main.courseMap[id].name + '</option>';
            }
        });
        $(form).find("#course-move-new-course").html(optionsHtml);
        $(form).find("#course-move-date").val('');
        $(form).find(".datepicker").datepicker(Main.datepickerDefaultSettings);
        $(form).find(".datepicker").datepicker("option", "minDate", $(e).data('date'));
        $("#modal-course-move").modal("show");
    },
    moveCourseStudent: function(form) {
        this.lockMoveStudentButton();
        return $.ajax({
            url: '/course/process-move-student',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-course-move").modal("hide");
                    Dashboard.toggleStudentInfo($('#user-view-' + data.userId), true, 'course');
                } else {
                    Main.throwFlashMessage('#course-move-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#course-move-messages-place');
            })
            .always(Dashboard.unlockMoveStudentButton);
    },
    lockMoveStudentButton: function() {
        $("#course-move-button").prop('disabled', true);
    },
    unlockMoveStudentButton: function() {
        $("#course-move-button").prop('disabled', false);
    },
    showMoveMoneyForm: function(e) {
        let courseStudentId = $(e).data('id');
        let courseId = parseInt($(e).data('course'));
        let course = Main.courseMap[courseId];
        $('#money-move-messages-place').html('');
        let form = $("#money-move-form");
        $(form).find("#money-move-id").val(courseStudentId);
        $(form).find("#money-move-amount").val($(e).data("amount"));
        $(form).find("#money-move-course").val(course.name);
        $(form).find("#money-move-student-name").val($(e).closest(".result-student").find(".student-name").text());
        let optionsHtml = '';
        let allowedCourseIds = $(e).data('courses');
        if (typeof allowedCourseIds === 'number') {
            allowedCourseIds = [allowedCourseIds];
        } else if (allowedCourseIds.length > 0) {
            allowedCourseIds = allowedCourseIds.split(',').map(function (x) {
                return parseInt(x, 10);
            });
        }
        if (allowedCourseIds.length > 0) {
            allowedCourseIds.forEach(function (courseId) {
                optionsHtml += '<option value="' + courseId + '">' + Main.courseMap[courseId].name + '</option>';
            });
        }
        $(form).find("#money-move-new-course").html(optionsHtml);
        $("#modal-money-move").modal("show");
    },
    moveMoney: function(form) {
        this.lockMoveMoneyButton();
        return $.ajax({
            url: '/course/process-move-money',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-money-move").modal("hide");
                    Dashboard.toggleStudentInfo($('#user-view-' + data.userId), true, 'course');
                } else {
                    Main.throwFlashMessage('#money-move-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#money-move-messages-place');
            })
            .always(Dashboard.unlockMoveMoneyButton);
    },
    lockMoveMoneyButton: function() {
        $("#money-move-button").prop('disabled', true);
    },
    unlockMoveMoneyButton: function() {
        $("#money-move-button").prop('disabled', false);
    },
    showEndStudentForm: function(e) {
        let courseStudentId = $(e).data('id');
        let courseId = parseInt($(e).data('course'));
        let course = Main.courseMap[courseId];
        $('#end-student-messages-place').html('');
        let form = $("#end-student-form");
        $(form).find("#end-student-id").val(courseStudentId);
        $(form).find("#end-student-course").val(course.name);
        $(form).find("#end-student-student-name").val($(e).closest(".result-student").find(".student-name").text());

        let courseLimitDate = $(e).data("date");
        let limitDate = this.studentLimitDate !== null && this.studentLimitDate > courseLimitDate ? this.studentLimitDate : courseLimitDate;
        $(form).find(".datepicker").datepicker(Main.datepickerDefaultSettings);
        $(form).find(".datepicker").datepicker("option", "minDate", new Date(limitDate));

        $("#modal-end-student").modal("show");
    },
    endStudent: function(form) {
        this.lockEndStudentButton();
        return $.ajax({
            url: '/course/end-student',
            type: 'post',
            dataType: 'json',
            data: $(form).serialize()
        })
            .done(function(data) {
                if (data.status === 'ok') {
                    $("#modal-end-student").modal("hide");
                    Dashboard.toggleStudentInfo($('#user-view-' + data.userId), true, 'course');
                } else {
                    Main.throwFlashMessage('#end-student-messages-place', 'Ошибка: ' + data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#end-student-messages-place');
            })
            .always(Dashboard.unlockEndStudentButton);
    },
    lockEndStudentButton: function() {
        $("#end-student-button").prop('disabled', true);
    },
    unlockEndStudentButton: function() {
        $("#end-student-button").prop('disabled', false);
    },
    filterPayments: function(e) {
        let container = $(e).closest(".view-payments");
        let filterCourse = $(container).find(".filter-course").val();
        let showExpenses = $(container).find(".filter-type").is(':checked');
        let paymentsTable = $(container).find("table.payments-table tbody");
        if (filterCourse > 0) {
            $(paymentsTable).find('tr').removeClass('show');
            $(paymentsTable).find('tr.course-' + filterCourse).addClass('show');
        } else {
            $(paymentsTable).find('tr').addClass('show');
        }
        
        if (!showExpenses) {
            $(paymentsTable).find('tr.expense').removeClass('show');
        }
    },
    filterCourses: function(e) {
        $(e).closest(".courses").find(".courses-table .course-item.inactive")
            .collapse($(e).is(':checked') ? 'hide' : 'show');
    },
    showCreateStudentForm: function() {
        $("#user-student-name").val($("input.search").val());
        User.init(true)
            .fail(Main.jumpToTop);
        $("#create_student_messages_place").html('');
        $('#modal-create-student').modal('show');
        if (1 !== MultiStepForm.currentStep) {
            MultiStepForm.jumpTo(1);
        }
    },
    clearCreateStudentForm: function() {
        let form = $("#create-student-form");
        $(form).find('select, textarea, input:not([type="radio"]):not([type="checkbox"])').each((index, elem) => {
            $(elem).val('');
        });
        $(form).find(".step-tab").removeClass(['step-success', 'step-invalid', 'active'])
        $(form).find('.consultation-item').remove();
        $(form).find('.welcome-lesson-item').remove();
        $(form).find('.course-item').remove();
        User.checkStudentPhoneRequired();
    },
    lockCreateStudentButton: function() {
        let button = $("#create-student-form").find('button[type=submit]');
        $(button).find('.button-loading-spinner').removeClass('d-none');
        $(button).prop('disabled', true);
    },
    unlockCreateStudentButton: function() {
        let button = $("#create-student-form").find('button[type=submit]');
        $(button).prop('disabled', false);
        $(button).find('.button-loading-spinner').addClass('d-none');
    },
    createStudent: function(form) {
        if (MultiStepForm.validate(form)) {
            this.lockCreateStudentButton(form);
            $.ajax({
                url: '/user/create-student',
                type: 'post',
                dataType: 'json',
                data: $(form).serialize()
            })
                .done(function(data) {
                    if ('ok' === data.status) {
                        Dashboard.clearCreateStudentForm();
                        $('#modal-create-student').modal('hide');
                        let searchForm = $("#search-form");
                        $(searchForm).find('input.search').val(data.name);
                        Dashboard.find(searchForm);
                    } else {
                        Main.throwFlashMessage('#create_student_messages_place', data.message, 'alert-danger');
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#create_student_messages_place');
                })
                .always(Dashboard.unlockCreateStudentButton);
        }
    },
    showAgeConfirmationForm: function(e) {
        let userId = $(e).data('user');
        $('#age-messages-place').html('');
        let form = $("#modal-age_confirmation");
        $(form).find("#age-user-id").val(userId);
        $(form).find("#age-student-name").val($(e).closest(".result-student").find(".student-name").text());

        let phones = [
            $(e).data('phone1'),
            $(e).data('phone2'),
            $(e).data('phone3'),
            $(e).data('phone4'),
            ];
        let phoneElem = [
            $(form).find('#age-phone-1'),
            $(form).find('#age-phone-2'),
            $(form).find('#age-phone-3'),
            $(form).find('#age-phone-4'),
            ];
        for (let i = 0; i < 4; i++) {
            if (phones[i].length > 0) {
                $(phoneElem[i]).data('phone', phones[i]);
                $(phoneElem[i]).text(phones[i]);
                $(phoneElem[i]).show();
            } else {
                $(phoneElem[i]).hide();
            }
        }

        $(form).modal("show");
    },
    sendAgeConfirmationSms: function(btn) {
        $.ajax({
            url: '/user/send-age-sms',
            type: 'post',
            dataType: 'json',
            data: {
                "user_id": $("#age-user-id").val(),
                "phone": $(btn).data('phone')
            }
        })
            .done(function(data) {
                if ('ok' === data.status) {
                    Main.throwFlashMessage('#age-messages-place', data.message, 'alert-success');
                } else {
                    Main.throwFlashMessage('#age-messages-place', data.message, 'alert-danger');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                Main.logAndFlashAjaxError(jqXHR, textStatus, errorThrown, '#age-messages-place');
            });
    }
};
