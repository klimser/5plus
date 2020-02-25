let User = {
    consultationList: [],
    welcomeLessonList: [],
    paymentList: [],
    teacherElement: $("#welcome_lesson_teacher"),
    
    loadTeacherSelect: function (e) {
        let subjectId = $(e).val();
        $(this.teacherElement).data("subject", subjectId);

        if (typeof this.teacherList[subjectId] === 'undefined') {
            $.get({
                url: '/teacher/list-json',
                dataType: 'json',
                data: {subject: subjectId},
                success: function (data) {
                    User.teacherList[data.subjectId] = [];
                    data.teachers.forEach(function(teacherId) {
                        User.teacherList[data.subjectId].push(teacherId);
                    });
                    User.fillTeacherSelect(data.subjectId);
                }
            });
        } else this.fillTeacherSelect(subjectId);
    },
    addWelcomeLesson: function(data) {
        if (data === undefined) {
            data = {};
        }
        let blockHtml = '<div class="welcome-lesson-item">';
        blockHtml += '</div>';
        
        $("#welcome_lessons").append(blockHtml);
    },
    fillTeacherSelect: function (subjectId) {
        if ($(this.teacherElement).data("subject") === subjectId) {
            $(this.teacherElement).html(this.getTeachersOptions(subjectId)).removeData("subject");
        }
    },
    getTeachersOptions: function (subjectId) {
        let list = '';
        this.teacherList[subjectId].forEach(function(teacherId) {
            list += '<option value="' + teacherId + '">' + User.teacherMap[teacherId] + '</option>';
        });
        return list;
    },
    findByPhone: function(phoneString, successHandler, errorHandler) {
        $.ajax({
            url: '/user/find-by-phone',
            type: 'post',
            data: {
                phone: phoneString
            },
            dataType: 'json',
            success: successHandler,
            error: errorHandler
        });
    },
    changePersonType: function() {
        switch ($('input.person_type:checked').val()) {
            case '2':
                $("#parents_block").removeClass('hidden');
                $("#company_block").addClass('hidden');
                break;
            case '4':
                $("#parents_block").addClass('hidden');
                $("#company_block").removeClass('hidden');
                break;
        }
    },
    changeParentType: function() {
        switch ($('input[name="parent_type"]:checked').val()) {
            case 'none':
                $("#parents_select").addClass('hidden');
                $("#parents_form").addClass('hidden');
                break;
            case 'exist':
                $("#parents_select").removeClass('hidden');
                $("#parents_form").addClass('hidden');
                $('#parents_select select.chosen').chosen({
                    disable_search_threshold: 6,
                    no_results_text: 'Не найдено',
                    placeholder_text_single: 'Выберите родителей'
                });
                break;
            case 'new':
                $("#parents_select").addClass('hidden');
                $("#parents_form").removeClass('hidden');
                break;
        }
    },
    changeCompanyType: function() {
        switch ($('input[name="company_type"]:checked').val()) {
            case 'exist':
                $("#company_select").removeClass('hidden');
                $("#company_form").addClass('hidden');
                $('#company_select select.chosen').chosen({
                    disable_search_threshold: 6,
                    no_results_text: 'Не найдено',
                    placeholder_text_single: 'Выберите компанию'
                });
                break;
            case 'new':
                $("#company_select").addClass('hidden');
                $("#company_form").removeClass('hidden');
                break;
        }
    },
    setAmountBlockVisibility: function() {
        if ($("#add_payment_switch").is(":checked") || $("#add_contract_switch").is(":checked")) {
            $("#amount_block").removeClass('hidden').find("input").prop("disabled", false);
        } else {
            $("#amount_block").addClass('hidden').find("input").prop("disabled", true);
        }
    },
    checkWelcomeLesson: function(e) {
        let lessonBlock = $('#add_welcome_lesson');
        if (e.checked) {
            lessonBlock.removeClass("hidden")
                .find("input, select").prop("disabled", false);
            let groupSwitch = $("#add_group_switch");
            if (groupSwitch.is(":checked")) {
                groupSwitch.prop("checked", false);
                this.checkAddGroup(groupSwitch);
            }
        } else {
            lessonBlock.addClass("hidden")
                .find("input, select").prop("disabled", true);
        }
    },
    setWelcomeLessonGroup: function(e) {
        if ($(e).val().length === 0) {
            $("#welcome_lesson_subject").prop("disabled", false);
            $("#welcome_lesson_teacher").prop("disabled", false);
        } else {
            $("#welcome_lesson_subject").prop("disabled", true);
            $("#welcome_lesson_teacher").prop("disabled", true);
        }
    },
    checkAddGroup: function(e) {
        let paymentSwitch = $("#add_payment_switch");
        let contractSwitch = $("#add_contract_switch");
        if (e.checked) {
            $('#add_group').removeClass("hidden")
                .find("input").prop("disabled", false);
            let lessonSwitch = $("#welcome_lesson_switch");
            if (lessonSwitch.is(":checked")) {
                lessonSwitch.prop("checked", false);
                this.checkWelcomeLesson(lessonSwitch);
            }
            if (paymentSwitch.length > 0) {
                paymentSwitch.prop("disabled", false);
            }
            if (contractSwitch.length > 0) {
                $("#contract_group_block").addClass("hidden");
            }
            this.setAmountHelperButtons($("#group"), true);
        } else {
            $('#add_group').addClass("hidden")
                .find("input").prop("disabled", true);
            if (contractSwitch.length > 0) {
                $("#contract_group_block").removeClass("hidden");
                if (contractSwitch.is(":checked")) {
                    this.setAmountHelperButtons($("#contract_group"), true);
                }
            }

            if (paymentSwitch.length > 0) {
                if (paymentSwitch.is(":checked")) {
                    paymentSwitch.click();
                }
                paymentSwitch.prop("disabled", true);
            }
        }
        this.setAmountBlockVisibility();
    },
    checkAddPayment: function(e) {
        let contractSwitch = $("#add_contract_switch");
        if (e.checked) {
            $('#amount_block').removeClass('hidden');
            $('#add_payment').removeClass('hidden')
                .find("input").prop('disabled', false);
            if (contractSwitch.length > 0 && contractSwitch.is(":checked")) {
                contractSwitch.click();
            }
        } else {
            $('#add_payment').addClass('hidden')
                .find("input").prop('disabled', true);
        }
        this.setAmountBlockVisibility();
    },
    checkAddContract: function(e) {
        let paymentSwitch = $("#add_payment_switch");
        if (e.checked) {
            $('#amount_block').removeClass('hidden');
            $('#add_contract').removeClass('hidden')
                .find('input[type!="checkbox"]').prop('disabled', false);
            if (paymentSwitch.length > 0 && paymentSwitch.is(":checked")) {
                paymentSwitch.click();
            }
        } else {
            $('#add_contract').addClass('hidden')
                .find('input[type!="checkbox"]').prop('disabled', true);
        }
        this.checkAddGroup($("#add_group_switch").get(0));
    },
    getHelperButtonHtml(amount, label) {
        return '<button type="button" class="btn btn-default btn-xs" onclick="User.setAmount(' + amount + ');">' + label + '</button>'
    },
    setAmountHelperButtons: function(select, flushAmount) {
        let opt = $(select).find("option:selected");
        if (flushAmount) $("#amount").val('');
        $("#amount_helper_buttons").html(
            this.getHelperButtonHtml($(opt).data('price'), 'за 1 месяц') +
            this.getHelperButtonHtml($(opt).data('price3'), 'за 3 месяца')
        );
    },
    setAmount: function(amount) {
        $("#amount").val(amount);
    },
    checkPhone(e) {
        this.phoneCheckInput = e;
        User.findByPhone(
            $(e).val(),
            function (data) {
                if (!User.phoneCheckInput) return;
                if ($(User.phoneCheckInput).val() !== data.phone) return;

                let messageBlock = $(User.phoneCheckInput).closest(".input-group").find(".help-block");
                if (data.pupils !== undefined && data.pupils.length > 0) {
                    let pupilList = '';
                    Money.pupils = {};
                    Money.groups = {};
                    data.pupils.forEach(function(pupil) {
                        pupilList += '<div>' + pupil.name + '</div>';
                    });

                    messageBlock.html('<b>Студенты с таким номером уже существуют!!!</b>' + pupilList);
                } else {
                    messageBlock.html('');
                }
                User.phoneCheckInput = null;
            },
            function (xhr, textStatus, errorThrown) {
                Main.throwFlashMessage('#messages_place', "Ошибка: " + textStatus + ' ' + errorThrown, 'alert-danger');
            }
        );
    },
    init: function() {
        $.when(Main.loadActiveSubjects(), Main.loadActiveGroups(), Main.loadActiveTeachers())
            .done(function(subjectList, groupList, teacherList) {
                
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
                Main.throwFlashMessage('#messages_place', 'Unable to load data, details in console log', 'alert-danger');
            });
        
        if ($("#add_group_switch").is(":checked")) this.setAmountHelperButtons($("#group"));
        else if ($("#add_contract_switch").is(":checked")) this.setAmountHelperButtons($("#contract_group"));
    }
};
