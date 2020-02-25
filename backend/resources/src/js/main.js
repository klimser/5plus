let Main = {
    throwFlashMessage: function (blockSelector, message, additionalClass, append) {
        if (typeof append !== 'boolean') append = false;
        var blockContent = '<div class="alert alert-dismissible ' + additionalClass + '"><button type="button" class="close" data-dismiss="alert" aria-label="Закрыть"><span aria-hidden="true">&times;</span></button>' + message + '</div>';
        if (append) $(blockSelector).append(blockContent);
        else $(blockSelector).html(blockContent);
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
    changeEntityStatus: function (entityType, entityId, newStatus, e, successCallback) {
        var postData = {
            status: newStatus
        };
        if (newStatus === 'problem') {
            var problemEdit = $("#problem-comment-" + entityId);
            if (!problemEdit.length) {
                $(e).after('<textarea id="problem-comment-' + entityId + '" placeholder="Комментарий к проблеме"></textarea>' +
                    '<button class="btn btn-default" value="problem" onclick="Main.changeEntityStatus(\'' + entityType + '\', ' + entityId + ', \'' + newStatus + '\', this);">OK</button>');
                return;
            } else if (!$(problemEdit).val().length) {
                return;
            } else {
                postData.comment = $(problemEdit).val();
            }
        }
        let successCallbacks = [function (data) {
            if (data.status === 'ok') {
                $('tr[data-key="' + data.id + '"] td').css({backgroundColor: '#dff0d8'}).animate({backgroundColor: '#dff0d8'}, 1000, function () {
                    $(this).css({backgroundColor: ''});
                });
            } else {
                alert(data.message);
            }
        }];
        if (successCallback) successCallbacks.push(successCallback);
        $.ajax({
            url: '/' + entityType + '/change-status?id=' + entityId,
            type: 'POST',
            dataType: 'json',
            data: postData,
            success: successCallbacks,
        });
    },
    submitSortableForm: function (form) {
        var sortable = $(form).find(".ui-sortable");
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

    cacheDeferred: {},
    createCache: function(key, requestFunction) {
        if (!this.cacheDeferred[key]) {
            this.cacheDeferred[key] = $.Deferred(function(defer) {
                requestFunction(defer);
            });
        }
        return this.cacheDeferred[key];
    },    
    groupActiveList: [],
    groupMap: {},
    loadActiveGroups: function() {
        return $.Deferred(function(defer) {
            if (Main.groupActiveList.length > 0) {
                defer.resolve(Main.groupActiveList);
            } else {
                $.getJSON('/ajax-info/groups', {filter: {active: 1}})
                .done(function(data) {
                    data.forEach(function(group) {
                        Main.groupActiveList.push(group.id);
                        Main.groupMap[group.id] = group;
                    });
                    defer.resolve(Main.groupActiveList);
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    defer.reject(jqXHR, textStatus, errorThrown);
                });
            }
        });
    },

    teacherActiveList: {},
    teacherMap: {},
    loadActiveTeachers: function () {
        return $.Deferred(function(defer) {
            if (Main.teacherActiveList.length > 0) {
                defer.resolve(Main.teacherActiveList);
            } else {
                $.getJSON('/ajax-info/teachers', {filter: {active: 1}})
                    .done(function(data) {
                        data.forEach(function(teacher) {
                            Main.teacherMap[teacher.id] = teacher;
                            teacher.subjectIds.forEach(function(subjectId) {
                                if (typeof Main.teacherActiveList[subjectId] === 'undefined') {
                                    Main.teacherActiveList[subjectId] = [];
                                }
                                Main.teacherActiveList[subjectId].push(teacher.id);
                            });
                        });
                        defer.resolve(Main.teacherActiveList);
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        defer.reject(jqXHR, textStatus, errorThrown);
                    });
            }
        });
    },
    
    subjectActiveList: {},
    subjectCategoryMap: {},
    subjectMap: {},
    loadActiveSubjects: function(defer, query) {
        return this.createCache('loadActiveSubjects', function(defer) {
            $.getJSON('/ajax-info/subjects', {filter: {active: 1}})
            .done(function(data) {
                data.forEach(function(subject) {
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
    // loadActiveSubjects: function () {
    //     return $.Deferred(function(defer) {
    //         if (Main.subjectActiveList.length > 0) {
    //             console.log('Cached');
    //             defer.resolve(Main.subjectActiveList);
    //         } else {
    //             $.getJSON('/ajax-info/subjects', {filter: {active: 1}})
    //                 .done(function(data) {
    //                     data.forEach(function(subject) {
    //                         Main.subjectMap[subject.id] = subject;
    //                         if (typeof Main.subjectCategoryMap[subject.categoryId] === 'undefined') {
    //                             Main.subjectCategoryMap[subject.categoryId] = subject.category;
    //                         }
    //                         if (typeof Main.subjectActiveList[subject.categoryId] === 'undefined') {
    //                             Main.subjectActiveList[subject.categoryId] = [];
    //                         }
    //                         Main.subjectActiveList[subject.categoryId].push(subject.id);
    //                     });
    //                     defer.resolve(Main.subjectActiveList);
    //                 })
    //                 .fail(function(jqXHR, textStatus, errorThrown) {
    //                     defer.reject(jqXHR, textStatus, errorThrown);
    //                 });
    //         }
    //     });
    // }
};
