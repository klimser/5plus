let Main = {
    datepickerDefaultSettings: $.extend({}, $.datepicker.regional['ru-RU'], {"firstDay":1,"dateFormat":"dd.mm.yy"}),
    throwFlashMessage: function (blockSelector, message, additionalClass, append) {
        if (typeof append !== 'boolean') append = false;
        var blockContent = '<div class="alert alert-dismissible fade show ' + additionalClass + '"><button type="button" class="close" data-dismiss="alert" aria-label="Закрыть"><span aria-hidden="true">&times;</span></button>' + message + '</div>';
        if (append) $(blockSelector).append(blockContent);
        else $(blockSelector).html(blockContent);
    },
    jumpToTop: function() {
        $('html, body').animate({ scrollTop: 0 }, 'fast');
    },
    changeEntityActive: function (entityType, entityId, e, activeValue) {
        var activityState = 0;
        if (activeValue !== undefined) {
            activityState = activeValue ? 1 : 0;
        } else {
            activityState = $(e).is(':checked') ? 1 : 0;
        }
        $.ajax({
            url: '/' + entityType + '/change-active?id=' + entityId,
            type: 'POST',
            dataType: 'json',
            data: {
                active: activityState
            },
            success: function (data) {
                if (data.status === 'ok') {
                    $('tr[data-key="' + data.id + '"] td').css({backgroundColor: '#dff0d8'}).animate({backgroundColor: '#dff0d8'}, 1000, function () {
                        $(this).css({backgroundColor: ''});
                    });
                } else {
                    alert(data.message);
                }
            }
        });
    },
    changeEntityStatus: function (entityType, entityId, newStatus, e) {
        let postData = {
            status: newStatus
        };
        if (newStatus === 'problem') {
            let problemEdit = $("#problem-comment-" + entityId);
            if (!problemEdit.length) {
                $(e).after('<textarea id="problem-comment-' + entityId + '" placeholder="Комментарий к проблеме"></textarea>' +
                    '<button class="btn btn-outline-dark" value="problem" onclick="Main.changeEntityStatus(\'' + entityType + '\', ' + entityId + ', \'' + newStatus + '\', this);">OK</button>');
                return null;
            } else if (!$(problemEdit).val().length) {
                return null;
            } else {
                postData.comment = $(problemEdit).val();
            }
        }
        return $.ajax({
            url: '/' + entityType + '/change-status?id=' + entityId,
            type: 'POST',
            dataType: 'json',
            data: postData,
        })
            .done(function (data) {
                if (data.status === 'ok') {
                    $('tr[data-key="' + data.id + '"] td').css({backgroundColor: '#dff0d8'}).animate({backgroundColor: '#dff0d8'}, 1000, function () {
                        $(this).css({backgroundColor: ''});
                    });
                } else {
                    alert(data.message);
                }
            });
    },
    submitSortableForm: function (form) {
        let sortable = $(form).find(".ui-sortable");
        if (sortable.length) {
            $(form).append('<input type="hidden" name="sorted-list" value="' + $(sortable).sortable("toArray") + '">');
        }
        return true;
    },
    initTooltip: function(selector = '[data-toggle="tooltip"]') {
        $(selector).tooltip();
    },
    initPhoneFormatted: function(selector) {
        if (selector === undefined) selector = ".phone-formatted";
        $(selector).inputmask({"mask": "99 999-9999"});
    },
    getDatepickerDate: function(datepickerInput) {
        let date;
        try {
            date = $.datepicker.parseDate($(datepickerInput).datepicker("option", "dateFormat"), $(datepickerInput).val());
        } catch(error) {
            date = null;
        }
        return date;
    },
    handleDateRangeFrom: function(elem) {
        let target;
        if ($(elem).data('targetToClosest')) {
            target = $(elem).closest($(elem).data('targetToClosest')).find($(elem).data('targetToSelector'));
        } else {
            target = $($(elem).data('targetToSelector'));
        }
        $(target).datepicker("option", "minDate", this.getDatepickerDate(elem));
    },
    handleDateRangeTo: function(elem) {
        let target;
        if ($(elem).data('targetFromClosest')) {
            target = $(elem).closest($(elem).data('targetFromClosest')).find($(elem).data('targetFromSelector'));
        } else {
            target = $($(elem).data('targetFromSelector'));
        }
        $(target).datepicker("option", "maxDate", this.getDatepickerDate(elem));
    },
    initAutocompleteUser: function(input) {
        if (!input || $(input).length === 0) return;
        let source = '/user/find';
        let role = $(input).data('role');
        if (role !== undefined) {
            source += '?role=' + role;
        }
        let resultsInput = $(input).parent().find("input.autocomplete-user-id");
        $(input).autocomplete({
            source: source,
            minLength: 2,
            change: function(event, ui) {
                let oldVal = $(resultsInput).val();
                if (ui.item === null) {
                    $(event.target).val('');
                    $(resultsInput).val('');
                } else {
                    $(resultsInput).val(ui.item.id);
                }
                if (oldVal !== $(resultsInput).val()) {
                    $(resultsInput).change();
                }
            }
        });
    },
    executeFunctionByName: function(functionName, context /*, args */) {
        let args = Array.prototype.slice.call(arguments, 2);
        let namespaces = functionName.split(".");
        let func = namespaces.pop();
        for(let i = 0; i < namespaces.length; i++) {
            if (typeof context[namespaces[i]] === 'undefined') {
                return false;
            }
            context = context[namespaces[i]];
        }
        if (typeof context[func] === 'function') {
            return context[func].apply(context, args);
        }
        return false;
    },
    
    logAndFlashAjaxError: function(jqXHR, textStatus, errorThrown, messagePlaceSelector) {
        console.log(jqXHR);
        console.log(textStatus);
        console.log(errorThrown);
        if (typeof messagePlaceSelector === 'undefined') {
            messagePlaceSelector = '#messages_place';
        }
        Main.throwFlashMessage(messagePlaceSelector, 'Server error, details in console log', 'alert-danger');
    },

    groupList: [],
    groupActiveList: [],
    groupMap: {},
    loadGroups: function(onlyActive = true) {
        let list = onlyActive ? this.groupActiveList : this.groupList;
        if (Object.keys(list).length > 0) {
            let defer = $.Deferred();
            defer.resolve(list);
            return defer;
        }
        
        let data = {};
        if (onlyActive) {
            data.filter = {active: 1};
        }
        return $.Deferred(function (defer) {
            $.getJSON('/ajax-info/groups', data)
                .done(function (data) {
                    let listFlushed = false;
                    if (data.find(function(group) {
                        return !group.active;
                    })) {
                        Main.groupList = [];
                        listFlushed = true;
                    }
                    Main.groupActiveList = [];
                    data.forEach(function (group) {
                        if (group.active) {
                            Main.groupActiveList.push(group.id);
                        }
                        if (listFlushed) {
                            Main.groupList.push(group.id);
                        }
                        Main.groupMap[group.id] = group;
                    });
                    defer.resolve(listFlushed ? Main.groupList : Main.groupActiveList);
                })
                .fail(defer.reject);
        });
    },

    teacherActiveList: [],
    teacherMap: {},
    loadActiveTeachers: function () {
        if (Object.keys(Main.teacherActiveList).length > 0) {
            let defer = $.Deferred();
            defer.resolve(Main.teacherActiveList);
            return defer;
        }

        return $.Deferred(function (defer) {
            $.getJSON('/ajax-info/teachers', {filter: {active: 1}})
                .done(function (data) {
                    Main.teacherActiveList = [];
                    data.forEach(function (teacher) {
                        Main.teacherMap[teacher.id] = teacher;
                        teacher.subjectIds.forEach(function (subjectId) {
                            if (typeof Main.teacherActiveList[subjectId] === 'undefined') {
                                Main.teacherActiveList[subjectId] = [];
                            }
                            Main.teacherActiveList[subjectId].push(teacher.id);
                        });
                    });
                    defer.resolve(Main.teacherActiveList);
                })
                .fail(defer.reject);
        });
    },
    
    subjectActiveList: [],
    subjectCategoryMap: {},
    subjectMap: {},
    loadActiveSubjects: function() {
        if (Object.keys(Main.subjectActiveList).length > 0) {
            let defer = $.Deferred();
            defer.resolve(Main.subjectActiveList);
            return defer;
        }

        return $.Deferred(function (defer) {
            $.getJSON('/ajax-info/subjects', {filter: {active: 1}})
                .done(function (data) {
                    Main.subjectActiveList = [];
                    data.forEach(function (subject) {
                        Main.subjectMap[subject.id] = subject;
                        if (typeof Main.subjectCategoryMap[subject.categoryId] === 'undefined') {
                            Main.subjectCategoryMap[subject.categoryId] = subject.category;
                        }
                        if (typeof Main.subjectActiveList[subject.categoryId] === 'undefined') {
                            Main.subjectActiveList[subject.categoryId] = [];
                        }
                        Main.subjectActiveList[subject.categoryId].push(subject.id);
                    });
                    defer.resolve(Main.subjectActiveList);
                })
                .fail(defer.reject);
        });
    }
};
