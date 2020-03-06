let User = {
    contractAllowed: false,
    incomeAllowed: false,
    pupilLimitDate: null,
    iterator: 1,
    consultationList: [],
    welcomeLessonList: [],
    groupList: [],
    
    getGroupOptions: function(selectedValue, addEmpty) {
        if (typeof addEmpty !== 'boolean') {
            addEmpty = false;
        }
        let optionsHtml = '';
        if (addEmpty) {
            optionsHtml += '<option value="0" ' + (selectedValue > 0 ? '' : 'selected') + '>Неизвестна</option>';
        }
        Main.groupActiveList.forEach(function(groupId) {
            optionsHtml += '<option value="' + groupId + '" ' + (selectedValue === groupId ? 'selected' : '') + '>'
                + Main.groupMap[groupId].name + '</option>';
        });
        return optionsHtml;
    },
    getSubjectOptions: function(selectedValue, addEmpty) {
        if (typeof addEmpty !== 'boolean') {
            addEmpty = false;
        }
        let optionsHtml = '';
        if (addEmpty) {
            optionsHtml += '<option value="0" ' + (selectedValue > 0 ? '' : 'selected') + '>Неизвестен</option>';
        }
        Object.keys(Main.subjectActiveList).forEach(function(categoryId) {
            optionsHtml += '<optgroup label="' + Main.subjectCategoryMap[categoryId] + '">';
            Main.subjectActiveList[categoryId].forEach(function(subjectId) {
                optionsHtml += '<option value="' + subjectId + '" ' + (selectedValue === subjectId ? 'selected' : '') + '>'
                    + Main.subjectMap[subjectId].name + '</option>';
            });
        });
        return optionsHtml;
    },
    getTeacherOptions: function(subjectId, selectedValue, addEmpty) {
        if (typeof addEmpty !== 'boolean') {
            addEmpty = false;
        }
        let optionsHtml = '';
        if (addEmpty) {
            optionsHtml += '<option value="0" ' + (selectedValue > 0 ? '' : 'selected') + '>Неизвестен</option>';
        }
        if (Main.teacherActiveList.hasOwnProperty(subjectId)) {
            Main.teacherActiveList[subjectId].forEach(function (teacherId) {
                optionsHtml += '<option value="' + teacherId + '" ' + (selectedValue === teacherId ? 'selected' : '') + '>'
                    + Main.teacherMap[teacherId].name + '</option>';
            });
        }
        return optionsHtml;
    },
    removeItem: function(e, itemClass) {
        let item = $(e).closest('.' + itemClass);
        if (item.length > 0) {
            $(item).remove();
        }
    },
    addConsultation: function(subjectId) {
        if (subjectId === undefined) {
            subjectId = 0;
        }

        let container = $("#consultation-mandatory");
        let blockHtml = '<div class="consultation-item panel panel-default"><div class="panel-body">';
        if ($(container).html().length > 0) {
            container = $("#consultation-optional");
            blockHtml += '<button type="button" class="close" aria-label="Close" onclick="User.removeItem(this, \'consultation-item\');"><span aria-hidden="true">&times;</span></button>';
        }
        blockHtml += '<div class="form-group">' +
            '<label>Предмет</label>' +
            '<select class="form-control subject-select" name="consultation[]">' + this.getSubjectOptions(subjectId) + '</select>' +
            '</div>';
        blockHtml += '</div></div>';
        $(container).append(blockHtml);
    },
    addWelcomeLesson: function addWelcomeLesson(data) {
        this.setPupilPhoneRequired(true);
        if (data === undefined) {
            data = {groupId: 0, subjectId: 0, teacherId: 0, date: ''};
        }

        let blockHtml = '<div class="welcome-lesson-item panel panel-default"><div class="panel-body">';
        blockHtml += '<div class="row">' +
            '<div class="col-xs-10 col-sm-11 col-md-4"><div class="form-group">' +
            '<label>Группа</label>' +
            '<select class="form-control group-select" name="welcome_lesson[groupId][' + this.iterator + ']" onchange="User.setWelcomeLessonGroup(this);">' +
            this.getGroupOptions(data.groupId, true) +
            '</select>' +
            '</div>' +
            '</div>';
        blockHtml += '<div class="col-xs-2 col-sm-1 col-md-push-7"><button type="button" class="close" aria-label="Close" onclick="User.removeItem(this, \'welcome-lesson-item\');"><span aria-hidden="true">&times;</span></button></div>';
        blockHtml += '<div class="col-xs-12 col-md-4 col-md-pull-1"><div class="form-group">' +
            '<label>Предмет</label>' +
            '<select class="form-control subject-select" name="welcome_lesson[subjectId][' + this.iterator + ']" onchange="User.setWelcomeLessonSubject(this);"' +
            (data.groupId > 0 ? ' disabled ' : '') + '>' + this.getSubjectOptions(data.subjectId) + '</select>' +
            '</div></div>';
        blockHtml += '<div class="col-xs-12 col-md-3 col-md-pull-1"><div class="form-group">' +
            '<label>Учитель</label>' +
            '<select class="form-control teacher-select" name="welcome_lesson[teacherId][' + this.iterator + ']"' + (data.groupId > 0 ? ' disabled ' : '') + '>' +
            this.getTeacherOptions(data.subjectId, data.teacherId) + '</select>' +
            '</div></div>';
        blockHtml += '</div>';
        blockHtml += '<div class="form-group">' +
            '<label>Дата</label>' +
            '<div class="input-group date datepicker">' +
            '<input type="text" class="form-control date-select" name="welcome_lesson[date][' + this.iterator + ']" value="' + data.date + '" required>' +
            '<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>' +
            '</div>' +
            '</div>';
        blockHtml += '</div></div>';
        let container = $("#welcome_lessons");
        $(container).append(blockHtml);
        
        this.iterator++;
        $(container).find('.welcome-lesson-item:last').find(".datepicker")
            .datepicker({autoclose: true, format: "dd.mm.yyyy", language: "ru", weekStart: 1});
        this.setWelcomeLessonSubject($(container).find('.welcome-lesson-item:last').find("select.subject-select"));
    },
    addGroup: function(data) {
        this.setPupilPhoneRequired(true);
        if (data === undefined) {
            data = {groupId: 0, date: '', amount: 0, company: 0, paymentComment: ''};
        }
        
        let blockHtml = '<div class="group-item panel panel-default"><div class="panel-body">';
        blockHtml += '<div class="row"><div class="col-xs-10 col-sm-11"><div class="form-group">' +
            '<label>Группа</label>' +
            '<select class="form-control group-select" name="group[groupId][' + this.iterator + ']" onchange="User.setGroup(this, true);" required>' +
            this.getGroupOptions(data.groupId) +
            '</select>' +
            '</div></div>';
        blockHtml += '<div class="col-xs-2 col-sm-1"><button type="button" class="close" aria-label="Close" onclick="User.removeItem(this, \'group-item\');"><span aria-hidden="true">&times;</span></button></div></div>';
        blockHtml += '<div class="form-group">' +
            '<label>Начало занятий</label>' +
            '<div class="radio">' +
            '<label>' +
            '<input type="radio" name="group[dateDefined][' + this.iterator + ']" value="0"' + (data.date.length > 0 ? '' : ' checked ') + ' onchange="User.setGroupDateType(this);" required>' +
            'ещё не решил (не добавлять в группу)' +
            '</label>' +
            '</div>' +
            '<div class="radio">' +
            '<label>' +
            '<input type="radio" name="group[dateDefined][' + this.iterator + ']" value="1"' + (data.date.length > 0 ? ' checked ' : '') + ' onchange="User.setGroupDateType(this);" required>' +
            '<div class="row"><div class="col-xs-3 col-md-1"> дата</div><div class="col-xs-9 col-md-4">' +
            '<div class="input-group date datepicker">' +
            '<input type="text" class="form-control date-select" name="group[date][' + this.iterator + ']" value="' + data.date + '" required>' +
            '<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>' +
            '</div></div></div>' +
            '</label>' +
            '</div>' +
            '</div>';
        if (this.contractAllowed || this.incomeAllowed) {
            blockHtml += '<div class="checkbox">' +
                '<label>' +
                '<input type="checkbox" name="group[contract][' + this.iterator + ']" value="1" ' + (data.hasOwnProperty('contract') ? ' checked ' : '') + ' onchange="User.checkAddContract(this);">' +
                ' выдать договор' +
                '</label>' +
                '</div>' +
                '<div class="contract-block ' + (data.hasOwnProperty('contract') ? '' : ' hidden ') + '">' +
                '<div class="form-group">' +
                '<label>Сумма</label>' +
                '<input class="form-control amount-input" name="group[amount][' + this.iterator + ']" type="number" step="1000" min="1000" required ' +
                (data.hasOwnProperty('contract') ? '' : ' disabled ') + ' value="' + data.amount + '">' +
                '<div class="amount-helper-buttons">' +
                '<button type="button" class="btn btn-default btn-xs price" onclick="User.setAmount(this);">за 1 месяц</button>' +
                '<button type="button" class="btn btn-default btn-xs price3" onclick="User.setAmount(this);">за 3 месяца</button>' +
                '</div>' +
                '</div>' +
                '<div class="company-block">';
            Main.companyList.forEach(function(company) {
                blockHtml += '<div class="radio">' +
                    '<label>' +
                    '<input type="radio" class="company-input" name="group[company][' + User.iterator + ']" value="' + company.id + '" ' + (data.company === company.id ? ' checked ' : '')
                    + (data.hasOwnProperty('contract') ? '' : ' disabled ') + ' required>' +
                    company.second_name +
                    '</label>' +
                    '</div>';
            });
            blockHtml += '</div>';
            
            if (this.incomeAllowed) {
                blockHtml += '<div class="checkbox">' +
                    '<label>' +
                    '<input type="checkbox" name="group[payment][' + this.iterator + ']" value="1" ' + (data.hasOwnProperty('payment') ? ' checked ' : '')
                    + ' onchange="User.checkAddPayment(this);">' + ' принять оплату' +
                    '</label>' +
                    '</div>' +
                    '<div class="form-group payment-comment-block ' + (data.hasOwnProperty('payment') ? '' : ' hidden ') + '">' +
                    '<label>Комментарий к платежу</label>' +
                    '<input class="form-control" name="group[paymentComment][' + this.iterator + ']" value="' + data.paymentComment + '">' +
                    '</div>';
            }
        }
        blockHtml += '</div></div></div>';
        let container = $("#groups");
        $(container).append(blockHtml);

        this.iterator++;
        let datepickerOptions = {
            autoclose: true,
            format: "dd.mm.yyyy",
            language: "ru",
            weekStart: 1
        };
        if (this.pupilLimitDate !== null) {
            datepickerOptions.startDate = this.pupilLimitDate;
        }
        let currentGroupItem = $(container).find('.group-item:last');
        $(currentGroupItem).find(".datepicker").datepicker(datepickerOptions);
        if (data.date.length === 0) {
            $(currentGroupItem).find(".date-select").prop('disabled', true);
        }
        User.setGroup($(currentGroupItem).find(".group-select"), false);
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
    setPupilPhoneRequired: function(isRequired) {
        $("#user-pupil-phoneformatted").prop('required', isRequired);
    },
    setWelcomeLessonGroup: function(e) {
        let container = $(e).closest('.welcome-lesson-item');
        if ($(e).val() > 0) {
            let group = Main.groupMap[$(e).val()];
            $(container).find(".subject-select").prop("disabled", true)
                .find('option[value=' + group.subjectId + ']').prop('selected', true);
            this.setWelcomeLessonSubject($(container).find(".subject-select"));
            $(container).find(".teacher-select").prop("disabled", true)
                .find('option[value=' + group.teacherId + ']').prop('selected', true);
        } else {
            $(container).find(".subject-select").prop("disabled", false);
            $(container).find(".teacher-select").prop("disabled", false);
        }
    },
    setWelcomeLessonSubject: function(e) {
        $(e).closest('.welcome-lesson-item').find(".teacher-select")
            .html(this.getTeacherOptions($(e).val()));
    },
    setGroup: function(e, flushAmount) {
        let group = Main.groupMap[$(e).val()];
        let container = $(e).closest(".group-item");
        let contractBlock = $(container).find(".contract-block");
        if (contractBlock.length > 0) {
            let helperButtonsBlock = $(contractBlock).find(".amount-helper-buttons");
            $(helperButtonsBlock).find("button.price").data({price: group.price});
            $(helperButtonsBlock).find("button.price3").data({price: group.price3});
            if (flushAmount) {
                $(contractBlock).find("input.amount").val('');
            }
        }
        let limitDate = group.dateStart !== null && group.dateStart > this.pupilLimitDate ? this.pupilLimitDate : group.dateStart;
        $(container).find(".datepicker").datepicker('setStartDate', limitDate);
    },
    setGroupDateType: function(e) {
        $(e).closest(".group-item").find(".date-select").prop("disabled", $(e).val() <= 0);
    },
    checkAddPayment: function(e) {
        let paymentCommentBlock = $(e).closest(".group-item").find(".payment-comment-block");
        if (e.checked) {
            $(paymentCommentBlock).removeClass("hidden");
        } else {
            $(paymentCommentBlock).addClass("hidden");
        }
    },
    checkAddContract: function(e) {
        let contatiner = $(e).closest(".group-item");
        $(contatiner).find(".amount-input").prop("disabled", !e.checked);
        $(contatiner).find(".company-input").prop("disabled", !e.checked);
        if (e.checked) {
            $(contatiner).find(".contract-block").removeClass('hidden');
        } else {
            $(contatiner).find(".contract-block").addClass('hidden');
        }
    },
    setAmount: function(e) {
        $(e).closest(".group-item").find(".amount-input").val($(e).data('price'));
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
        $.when(Main.loadCompanies(), Main.loadActiveSubjects(), Main.loadActiveGroups(), Main.loadActiveTeachers())
            .done(function(companyList, subjectList, groupList, teacherList) {
                if (User.consultationList.length > 0) {
                    User.consultationList.forEach(function(subjectId) {
                        User.addConsultation(subjectId);
                    });
                } else {
                    User.addConsultation();
                }

                User.welcomeLessonList.forEach(function(welcomeLessonData) {
                    User.addWelcomeLesson(welcomeLessonData);
                });
                
                User.groupList.forEach(function(groupData) {
                    User.addGroup(groupData);
                });
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
                Main.throwFlashMessage('#messages_place', 'Unable to load data, details in console log', 'alert-danger');
            });
    }
};
