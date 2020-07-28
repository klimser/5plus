let User = {
    contractAllowed: false,
    incomeAllowed: false,
    pupilLimitDate: null,
    iterator: 1,
    consultationList: [],
    welcomeLessonList: [],
    groupList: [],

    init: function(noAdd) {
        return $.when(Main.loadActiveSubjects(), Main.loadActiveGroups(), Main.loadActiveTeachers())
            .done(function() {
                let autocompleteInputs = $("input.autocomplete-user");
                if (autocompleteInputs.length > 0) {
                    $(autocompleteInputs).each(function () {
                        Main.initAutocompleteUser(this);
                    });
                }
                
                if (noAdd === true) return;

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
            .fail(Main.logAndFlashAjaxError);
    },
    
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
    removeConsultation: function(e) {
        this.removeItem(e, 'consultation-item');
    },
    removeWelcomeLesson: function(e) {
        this.removeItem(e, 'welcome-lesson-item');
        this.checkPupilPhoneRequired();
    },
    removeGroup: function(e) {
        this.removeItem(e, 'group-item');
        this.checkPupilPhoneRequired();
    },
    addConsultation: function(subjectId, parentContainer) {
        if (subjectId === undefined) {
            subjectId = 0;
        }
        if (parentContainer === undefined) {
            parentContainer = document;
        }

        let container = $(parentContainer).find(".consultation-mandatory");
        let blockHtml = '<div class="consultation-item card mb-3"><div class="card-body p-3">';
        if ($(container).html().length > 0) {
            container = $(parentContainer).find(".consultation-optional");
            blockHtml += '<button type="button" class="close" aria-label="Close" onclick="User.removeConsultation(this);"><span aria-hidden="true">&times;</span></button>';
        }
        blockHtml += '<label>Предмет</label>' +
            '<select class="form-control subject-select" name="consultation[]" autocomplete="off">' + this.getSubjectOptions(subjectId) + '</select>';
        blockHtml += '</div></div>';
        $(container).append(blockHtml);
    },
    addWelcomeLesson: function addWelcomeLesson(data, parentContainer) {
        this.setPupilPhoneRequired(true);
        if (data === undefined) {
            data = {groupId: 0, subjectId: 0, teacherId: 0, date: ''};
        }
        if (parentContainer === undefined) {
            parentContainer = document;
        }

        let blockHtml = '<div class="welcome-lesson-item card mb-3"><div class="card-body p-3">';
        blockHtml += '<div class="row">' +
            '<div class="col-10 col-md-11 col-lg-4"><div class="form-group">' +
            '<label>Группа</label>' +
            '<select class="form-control group-select" name="welcome_lesson[groupId][' + this.iterator + ']" autocomplete="off" onchange="User.setWelcomeLessonGroup(this);">' +
            this.getGroupOptions(parseInt(data.groupId), true) +
            '</select>' +
            '</div>' +
            '</div>';
        blockHtml += '<div class="col-2 col-md-1 order-lg-last"><button type="button" class="close" aria-label="Close" onclick="User.removeWelcomeLesson(this);"><span aria-hidden="true">&times;</span></button></div>';
        blockHtml += '<div class="col-12 col-lg-4"><div class="form-group">' +
            '<label>Предмет</label>' +
            '<select class="form-control subject-select" name="welcome_lesson[subjectId][' + this.iterator + ']" autocomplete="off" onchange="User.setWelcomeLessonSubject(this);"' +
            (data.groupId > 0 ? ' disabled ' : '') + '>' + this.getSubjectOptions(data.subjectId) + '</select>' +
            '</div></div>';
        blockHtml += '<div class="col-12 col-lg-3"><div class="form-group">' +
            '<label>Учитель</label>' +
            '<select class="form-control teacher-select" name="welcome_lesson[teacherId][' + this.iterator + ']" autocomplete="off"' + (data.groupId > 0 ? ' disabled ' : '') + '>' +
            this.getTeacherOptions(data.subjectId, data.teacherId) + '</select>' +
            '</div></div>';
        blockHtml += '</div>';
        blockHtml += '<div class="form-group">' +
            '<label>Дата</label>' +
            '<input type="text" class="form-control date-select datepicker" name="welcome_lesson[date][' + this.iterator + ']" autocomplete="off" value="' + data.date + '" required>' +
            '</div>';
        blockHtml += '</div></div>';
        let container = $(parentContainer).find(".welcome_lessons");
        $(container).append(blockHtml);
        
        this.iterator++;
        $(container).find(".welcome-lesson-item:last .datepicker").datepicker(Main.datepickerDefaultSettings);
        this.setWelcomeLessonSubject($(container).find(".welcome-lesson-item:last select.subject-select"));
    },
    addGroup: function(data, parentContainer) {
        this.setPupilPhoneRequired(true);
        if (data === undefined) {
            data = {groupId: 0, date: '', amount: 0, paymentComment: ''};
        }
        if (parentContainer === undefined) {
            parentContainer = document;
        }
        if (!data.hasOwnProperty('date')) {
            data.date = '';
        }
        
        let blockHtml = '<div class="group-item card mb-3"><div class="card-body p-3">';
        blockHtml += '<button type="button" class="close" aria-label="Close" onclick="User.removeGroup(this);"><span aria-hidden="true">&times;</span></button>';
        blockHtml += '<div class="form-group">' +
            '<label>Группа</label>' +
            '<select class="form-control group-select" name="group[groupId][' + this.iterator + ']" autocomplete="off" onchange="User.setGroup(this, true);" required>' +
            this.getGroupOptions(parseInt(data.groupId)) +
            '</select>' +
            '</div>';
        blockHtml += '<div class="form-group">' +
            '<label>Начало занятий</label>' +
            '<div class="form-check">' +
            '<input class="form-check-input" type="radio" name="group[dateDefined][' + this.iterator + ']" id="group-date-defined-0-' + this.iterator + '"' +
            ' value="0"' + (data.date.length > 0 ? '' : ' checked ') + ' onchange="User.setGroupDateType(this);" required>' +
            '<label class="form-check-label" for="group-date-defined-0-' + this.iterator + '">ещё не решил (не добавлять в группу)</label>' +
            '</div>' +
            '<div class="form-check">' +
            '<input class="form-check-input" type="radio" name="group[dateDefined][' + this.iterator + ']" id="group-date-defined-1-' + this.iterator + '"' +
            ' value="1"' + (data.date.length > 0 ? ' checked ' : '') + ' onchange="User.setGroupDateType(this);" required>' +
            '<label class="form-check-label d-flex" for="group-date-defined-1-' + this.iterator + '">' +
            '<div class="row w-100"><div class="col-3 col-lg-1"> дата</div><div class="col-9 col-lg-4">' +
            '<input type="text" class="form-control date-select datepicker" name="group[date][' + this.iterator + ']" autocomplete="off" value="' + data.date + '" required>' +
            '</div></div>' +
            '</label>' +
            '</div>' +
            '</div>';
        if (this.contractAllowed || this.incomeAllowed) {
            blockHtml += '<div class="form-check">' +
                '<input class="form-check-input" type="checkbox" name="group[contract][' + this.iterator + ']" autocomplete="off"' +
                ' value="1" id="group-contract-' + this.iterator + '" ' + (data.hasOwnProperty('contract') ? ' checked ' : '') + ' onchange="User.checkAddContract(this);">' +
                '<label class="form-check-label" for="group-contract-' + this.iterator + '">' +
                'выдать договор' +
                '</label>' +
                '</div>' +
                '<div class="contract-block collapse ' + (data.hasOwnProperty('contract') ? ' show ' : '') + '">' +
                '<div class="form-group">' +
                '<label>Сумма</label>' +
                '<input class="form-control income-amount" name="group[amount][' + this.iterator + ']" autocomplete="off" type="number" step="1000" min="1000" required ' +
                (data.hasOwnProperty('contract') ? '' : ' disabled ') + ' value="' + data.amount + '">' +
                '<div class="amount-helper-buttons">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm price" onclick="Dashboard.setAmount(this);">за 1 месяц</button>' +
                '<button type="button" class="btn btn-outline-secondary btn-sm price3" onclick="Dashboard.setAmount(this);">за 3 месяца</button>' +
                '</div>' +
                '</div>';

            if (this.incomeAllowed) {
                blockHtml += '<div class="form-check">' +
                    '<input class="form-check-input" type="checkbox" name="group[payment][' + this.iterator + ']" autocomplete="off"' +
                    ' value="1" id="group-payment-' + this.iterator + '" ' + (data.hasOwnProperty('payment') ? ' checked ' : '') + ' onchange="User.checkAddPayment(this);">' +
                    '<label class="form-check-label" for="group-payment-' + this.iterator + '">' +
                    'принять оплату' +
                    '</label>' +
                    '</div>' +
                    '<div class="form-group payment-comment-block collapse ' + (data.hasOwnProperty('payment') ? ' show ' : '') + '">' +
                    '<label>Комментарий к платежу</label>' +
                    '<input class="form-control" name="group[paymentComment][' + this.iterator + ']" autocomplete="off" value="' + data.paymentComment + '">' +
                    '</div>';
            }
        }
        blockHtml += '</div></div></div>';
        let container = $(parentContainer).find(".groups");
        $(container).append(blockHtml);

        this.iterator++;
        let datepickerOptions = Main.datepickerDefaultSettings;
        if (this.pupilLimitDate !== null) {
            datepickerOptions.minDate = this.pupilLimitDate;
        }
        let currentGroupItem = $(container).find('.group-item:last');
        $(currentGroupItem).find(".datepicker").datepicker(datepickerOptions);
        if (data.date.length === 0) {
            $(currentGroupItem).find(".date-select").prop('disabled', true);
        }
        User.setGroup($(currentGroupItem).find(".group-select"), false);
    },
    
    findByPhone: function(phoneString) {
        return $.ajax({
            url: '/user/find-by-phone',
            type: 'post',
            data: {
                phone: phoneString
            },
            dataType: 'json'
        });
    },
    changePersonType: function() {
        switch ($('input.person_type:checked').val()) {
            case '2':
                $("#parents_block").collapse("show");
                $("#company_block").collapse("hide");
                $("#company_block .parent-edit-option input").prop("disabled", true);
                break;
            case '4':
                $("#company_block").collapse("show");
                $("#parents_block").collapse("hide");
                $("#parents_block .parent-edit-option input").prop("disabled", true);
                break;
        }
    },
    checkPupilPhoneRequired: function() {
        let requiredBlocksCount = $(".welcome-lesson-item").length + $(".group-item").length;
        if (requiredBlocksCount === 0) {
            this.setPupilPhoneRequired(false);
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
        let limitDate = group.pupilLimitDate !== null && this.pupilLimitDate > group.dateStart ? this.pupilLimitDate : group.dateStart;
        $(container).find(".datepicker").datepicker("option", "minDate", new Date(limitDate));
    },
    setGroupDateType: function(e) {
        $(e).closest(".group-item").find(".date-select").prop("disabled", $(e).val() <= 0);
    },
    checkAddPayment: function(e) {
        $(e).closest(".group-item").find(".payment-comment-block").collapse(e.checked ? 'show' : 'hide');
    },
    checkAddContract: function(e) {
        let contatiner = $(e).closest(".group-item");
        $(contatiner).find(".income-amount").prop("disabled", !e.checked);
        $(contatiner).find(".company-input").prop("disabled", !e.checked);
        $(contatiner).find(".contract-block").collapse(e.checked ? 'show' : 'hide');
    },
    checkPhone(e) {
        this.phoneCheckInput = e;
        User.findByPhone($(e).val())
            .done(function (data) {
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
            })
            .fail(Main.logAndFlashAjaxError);
    }
};
