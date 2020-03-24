let Main = {
    datepickerDefaultSettings: {autoclose: true, format: "dd.mm.yyyy", language: "ru", weekStart: 1},
    throwFlashMessage: function (blockSelector, message, additionalClass, append) {
        if (typeof append !== 'boolean') append = false;
        var blockContent = '<div class="alert alert-dismissible ' + additionalClass + '"><button type="button" class="close" data-dismiss="alert" aria-label="Закрыть"><span aria-hidden="true">&times;</span></button>' + message + '</div>';
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
                    '<button class="btn btn-default" value="problem" onclick="Main.changeEntityStatus(\'' + entityType + '\', ' + entityId + ', \'' + newStatus + '\', this);">OK</button>');
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
    initTooltip: function() {
        $('[data-toggle="tooltip"]').tooltip();
    },
    initPhoneFormatted: function(selector) {
        if (selector === undefined) selector = ".phone-formatted";
        $(selector).inputmask({"mask": "99 999-9999"});
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

    groupActiveList: [],
    groupMap: {},
    loadActiveGroups: function() {
        if (Object.keys(Main.groupActiveList).length > 0) {
            let defer = $.Deferred();
            defer.resolve(Main.groupActiveList);
            return defer;
        }
        
        return $.Deferred(function (defer) {
            $.getJSON('/ajax-info/groups', {filter: {active: 1}})
                .done(function (data) {
                    data.forEach(function (group) {
                        Main.groupActiveList.push(group.id);
                        Main.groupMap[group.id] = group;
                    });
                    defer.resolve(Main.groupActiveList);
                })
                .fail(defer.reject);
        });
    },

    teacherActiveList: {},
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
    
    subjectActiveList: {},
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
