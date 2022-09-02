let User = {
    contractAllowed: false,
    incomeAllowed: false,
    studentLimitDate: null,
    iterator: 1,
    consultationList: [],
    welcomeLessonList: [],
    courseList: [],

    init: function(noAdd) {
        return $.when(Main.loadActiveSubjects(), Main.loadCourses(), Main.loadActiveTeachers())
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

                User.courseList.forEach(function(courseData) {
                    User.addCourse(courseData);
                });
            })
            .fail(Main.logAndFlashAjaxError);
    },
    
    getCourseOptions: function(selectedValue, addEmpty, filter) {
        if (typeof addEmpty !== 'boolean') {
            addEmpty = false;
        }
        if (filter === undefined) {
            filter = {};
        }
        let optionsHtml = '';
        if (addEmpty) {
            optionsHtml += '<option value="" ' + (selectedValue > 0 ? '' : 'selected') + '>Неизвестна</option>';
        }
        Main.courseActiveList.forEach(function(courseId) {
            let allowed = true;
            Object.keys(filter).forEach(function(filterKey) {
                if (Main.courseMap[courseId][filterKey] !== filter[filterKey]) {
                    allowed = false;
                }
            });
            if (allowed) {
                optionsHtml += '<option value="' + courseId + '" ' + (selectedValue === courseId ? 'selected' : '') + '>'
                    + Main.courseMap[courseId].name + '</option>';
            }
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
        this.checkStudentPhoneRequired();
    },
    removeCourse: function(e) {
        this.removeItem(e, 'course-item');
        this.checkStudentPhoneRequired();
    },
    addConsultation: function(subjectId, parentContainer) {
        if (subjectId === undefined) {
            subjectId = 0;
        }
        if (parentContainer === undefined) {
            parentContainer = document;
        }

        let container = $(parentContainer).find(".consultations");
        let blockHtml = '<div class="consultation-item card mb-3"><div class="card-body p-3">' +
            '<button type="button" class="close" aria-label="Close" onclick="User.removeConsultation(this);"><span aria-hidden="true">&times;</span></button>' +
            '<label>Предмет</label>' +
            '<select class="form-control subject-select" name="consultation[]" autocomplete="off">' + this.getSubjectOptions(subjectId) + '</select>' +
            '</div></div>';
        $(container).append(blockHtml);
    },
    addWelcomeLesson: function (data, parentContainer) {
        this.setStudentPhoneRequired(true);
        if (data === undefined) {
            data = {groupId: 0, subjectId: 0, teacherId: 0, date: ''};
        }
        if (parentContainer === undefined) {
            parentContainer = document;
        }

        let blockHtml = '<div class="welcome-lesson-item card mb-3"><div class="card-body p-3">';
        blockHtml += '<div class="row">';
        blockHtml += '<div class="col-10 col-md-11 col-lg-6"><div class="form-group">' +
            '<label>Предмет</label>' +
            '<select class="form-control subject-select" name="welcome_lesson[subjectId][' + this.iterator + ']" autocomplete="off" onchange="User.setWelcomeLessonSubject(this);"' +
            (data.groupId > 0 ? ' disabled ' : '') + '>' + this.getSubjectOptions(data.subjectId) + '</select>' +
            '</div></div>';
        blockHtml += '<div class="col-2 col-md-1 order-lg-last"><button type="button" class="close" aria-label="Close" onclick="User.removeWelcomeLesson(this);"><span aria-hidden="true">&times;</span></button></div>';
        blockHtml += '<div class="col-12 col-lg-5"><div class="form-group">' +
            '<label>Учитель</label>' +
            '<select class="form-control teacher-select" name="welcome_lesson[teacherId][' + this.iterator + ']" autocomplete="off" onchange="User.setWelcomeLessonTeacher(this);"' +
            (data.groupId > 0 ? ' disabled ' : '') + '>' +
            this.getTeacherOptions(data.subjectId, data.teacherId) + '</select>' +
            '</div></div>';
        blockHtml += '</div>';
        blockHtml += '<div class="row">';
        blockHtml +=
            '<div class="col-12"><div class="form-group">' +
            '<label>Группа</label>' +
            '<select class="form-control group-select" name="welcome_lesson[groupId][' + this.iterator + ']" autocomplete="off" required onchange="User.setWelcomeLessonGroup(this);">' +
            this.getCourseOptions(parseInt(data.groupId), true) +
            '</select>' +
            '</div>' +
            '</div>';
        blockHtml += '</div>';
        blockHtml += '<div class="row">';
        blockHtml += '<div class="col-12"><div class="form-group">' +
            '<label>Дата</label>' +
            '<input type="text" class="form-control date-select datepicker" name="welcome_lesson[date][' + this.iterator + ']" autocomplete="off" value="' + data.date + '" required>' +
            '</div></div>';
        blockHtml += '</div>';
        blockHtml += '</div></div>';
        let container = $(parentContainer).find(".welcome_lessons");
        $(container).append(blockHtml);
        
        this.iterator++;
        $(container).find(".welcome-lesson-item:last .datepicker").datepicker(Main.datepickerDefaultSettings);
        this.setWelcomeLessonSubject($(container).find(".welcome-lesson-item:last select.subject-select"));
    },
    addCourse: function(data, parentContainer) {
        this.setStudentPhoneRequired(true);
        if (data === undefined) {
            data = {courseId: 0, date: '', amount: 0, paymentComment: ''};
        }
        if (parentContainer === undefined) {
            parentContainer = document;
        }
        if (!data.hasOwnProperty('date')) {
            data.date = '';
        }
        
        let blockHtml = '<div class="course-item card mb-3"><div class="card-body p-3">';
        blockHtml += '<button type="button" class="close" aria-label="Close" onclick="User.removeCourse(this);"><span aria-hidden="true">&times;</span></button>';
        blockHtml += '<div class="form-group">' +
            '<label>Группа</label>' +
            '<select class="form-control course-select" name="course[courseId][' + this.iterator + ']" autocomplete="off" onchange="User.setCourse(this, true);" required>' +
            this.getCourseOptions(parseInt(data.groupId)) +
            '</select>' +
            '</div>';
        blockHtml += '<div class="form-group">' +
            '<label>Начало занятий</label>' +
            '<div class="form-check">' +
            '<input class="form-check-input" type="radio" name="course[dateDefined][' + this.iterator + ']" id="course-date-defined-0-' + this.iterator + '"' +
            ' value="0"' + (data.date.length > 0 ? '' : ' checked ') + ' onchange="User.setCourseDateType(this);" required>' +
            '<label class="form-check-label" for="course-date-defined-0-' + this.iterator + '">ещё не решил (не добавлять в группу)</label>' +
            '</div>' +
            '<div class="form-check">' +
            '<input class="form-check-input" type="radio" name="course[dateDefined][' + this.iterator + ']" id="course-date-defined-1-' + this.iterator + '"' +
            ' value="1"' + (data.date.length > 0 ? ' checked ' : '') + ' onchange="User.setCourseDateType(this);" required>' +
            '<label class="form-check-label d-flex" for="course-date-defined-1-' + this.iterator + '">' +
            '<div class="row w-100"><div class="col-3 col-lg-auto"> дата</div><div class="col-9 col-lg-auto">' +
            '<input type="text" class="form-control date-select datepicker" name="course[date][' + this.iterator + ']" autocomplete="off" value="' + data.date + '" required>' +
            '</div></div>' +
            '</label>' +
            '</div>' +
            '</div>';
        // if (this.contractAllowed || this.incomeAllowed) {
        //     blockHtml += '<div class="form-check">' +
        //         '<input class="form-check-input" type="checkbox" name="group[payment][' + this.iterator + ']" autocomplete="off"' +
        //         ' value="1" id="group-payment-' + this.iterator + '" ' + (data.hasOwnProperty('payment') ? ' checked ' : '') + ' onchange="User.checkAddPayment(this);">' +
        //         '<label class="form-check-label" for="group-payment-' + this.iterator + '">' +
        //         'принять оплату' +
        //         '</label>' +
        //         '</div>' +
        //         '<div class="payment-block collapse ' + (data.hasOwnProperty('payment') ? ' show ' : '') + '">' +
        //         '<div class="form-group">' +
        //         '<label>Сумма</label>' +
        //         '<input class="form-control income-amount" name="group[amount][' + this.iterator + ']" autocomplete="off" type="number" step="1000" min="1000" required ' +
        //         (data.hasOwnProperty('payment') ? '' : ' disabled ') + ' value="' + data.amount + '">' +
        //         '<div class="amount-helper-buttons">' +
        //         '<button type="button" class="btn btn-outline-secondary btn-sm price" onclick="Dashboard.setAmount(this);">за 1 месяц</button>' +
        //         '<button type="button" class="btn btn-outline-secondary btn-sm price4" onclick="Dashboard.setAmount(this);">за 4 месяца</button>' +
        //         '</div>' +
        //         '</div>' +
        //         '<div class="form-group payment-comment-block">' +
        //         '<label>Комментарий к платежу</label>' +
        //         '<input class="form-control" name="group[paymentComment][' + this.iterator + ']" autocomplete="off" value="' + data.paymentComment + '">' +
        //         '</div>' +
        //         '</div>';
        // }
        blockHtml += '</div></div>';
        let container = $(parentContainer).find(".courses");
        $(container).append(blockHtml);

        this.iterator++;
        let datepickerOptions = Main.datepickerDefaultSettings;
        if (this.studentLimitDate !== null) {
            datepickerOptions.minDate = this.studentLimitDate;
        }
        let currentCourseItem = $(container).find('.course-item:last');
        $(currentCourseItem).find(".datepicker").datepicker(datepickerOptions);
        if (data.date.length === 0) {
            $(currentCourseItem).find(".date-select").prop('disabled', true);
        }
        User.setCourse($(currentCourseItem).find(".course-select"), false);
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
        let parentsBlock = $("#parents_block");
        let companyBlock = $("#company_block");
        let checkedVal, activeBlock;
        switch ($('input.person_type:checked').val()) {
            case '2':
                $(parentsBlock).collapse("show");
                $(companyBlock).collapse("hide");

                $(parentsBlock).find(".parent-edit-option input").prop("disabled", true);
                checkedVal = $(parentsBlock).find('input[name="parent_type"]:checked').val();
                activeBlock = $(parentsBlock).find(".parent-edit-" + checkedVal);
                if (activeBlock.length > 0) {
                    $(activeBlock).find("input").prop("disabled", false);
                }

                $(companyBlock).find(".parent-edit-option input").prop("disabled", true);
                break;
            case '4':
                $(parentsBlock).collapse("hide");
                $(companyBlock).collapse("show");

                $(companyBlock).find(".parent-edit-option input").prop("disabled", true);
                checkedVal = $(companyBlock).find('input[name="company_type"]:checked').val();
                activeBlock = $(companyBlock).find(".parent-edit-" + checkedVal);
                if (activeBlock.length > 0) {
                    $(activeBlock).find("input").prop("disabled", false);
                }

                $(parentsBlock).find(".parent-edit-option input").prop("disabled", true);
                break;
        }
    },
    checkStudentPhoneRequired: function() {
        let requiredBlocksCount = $(".welcome-lesson-item").length + $(".course-item").length;
        if (requiredBlocksCount === 0) {
            this.setStudentPhoneRequired(false);
        }
    },
    setStudentPhoneRequired: function(isRequired) {
        let phoneInput = $("#user-student-phoneformatted");
        if (phoneInput.length > 0) {
            $(phoneInput).prop('required', isRequired);
        }
    },
    setWelcomeLessonGroup: function(e) {
        let container = $(e).closest('.welcome-lesson-item');
        if ($(e).val() > 0) {
            let group = Main.courseMap[$(e).val()];
            $(container).find(".subject-select").prop("disabled", true)
                .find('option[value=' + group.subjectId + ']').prop('selected', true);
            $(container).find(".teacher-select").prop("disabled", true)
                .find('option[value=' + group.teacherId + ']').prop('selected', true);
            let dateSelect = $(container).find(".date-select");
            $(dateSelect).datepicker("option", "minDate", new Date(group.dateStart));
            $(dateSelect).datepicker("option", "beforeShowDay", function(date) {
                return [group.weekDays.indexOf(date.getDay()) >= 0, ""];
            });
            $(dateSelect).val('');
        } else {
            $(container).find(".subject-select").prop("disabled", false);
            $(container).find(".teacher-select").prop("disabled", false);
        }
    },
    setWelcomeLessonSubject: function(e) {
        $(e).closest('.welcome-lesson-item').find(".teacher-select")
            .html(this.getTeacherOptions($(e).val()));
        this.filterCourseSelect($(e).closest('.welcome-lesson-item'));
    },
    setWelcomeLessonTeacher: function(e) {
        this.filterCourseSelect($(e).closest('.welcome-lesson-item'));
    },
    filterCourseSelect: function(welcomeLessonItem) {
        let filter = {};
        if ($(welcomeLessonItem).find(".subject-select").val() > 0) {
            filter.subjectId = parseInt($(welcomeLessonItem).find(".subject-select").val());
        }
        if ($(welcomeLessonItem).find(".teacher-select").val() > 0) {
            filter.teacherId = parseInt($(welcomeLessonItem).find(".teacher-select").val());
        }
        let courseSelect = $(welcomeLessonItem).find(".course-select");
        $(courseSelect).html(this.getCourseOptions($(courseSelect).val(), true, filter));
    },
    setCourse: function(e, flushAmount) {
        let course = Main.courseMap[$(e).val()];
        let container = $(e).closest(".course-item");
        let paymentBlock = $(container).find(".payment-block");
        if (paymentBlock.length > 0) {
            let helperButtonsBlock = $(paymentBlock).find(".amount-helper-buttons");
            $(helperButtonsBlock).find("button.price-lesson").data({price: course.priceLesson});
            $(helperButtonsBlock).find("button.price-month").data({price: course.priceMonth});
            if (flushAmount) {
                $(paymentBlock).find("input.amount").val('');
            }
        }
        let limitDate = course.studentLimitDate !== null && this.studentLimitDate > course.dateStart ? this.studentLimitDate : course.dateStart;
        $(container).find(".datepicker").datepicker("option", "minDate", new Date(limitDate));
    },
    setCourseDateType: function(e) {
        $(e).closest(".course-item").find(".date-select").prop("disabled", $(e).val() <= 0);
    },
    checkAddPayment: function(e) {
        let contatiner = $(e).closest(".group-item");
        $(contatiner).find(".income-amount").prop("disabled", !e.checked);
        $(contatiner).find(".company-input").prop("disabled", !e.checked);
        $(contatiner).find(".payment-block").collapse(e.checked ? 'show' : 'hide');
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
