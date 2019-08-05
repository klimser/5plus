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
    initPhoneFormatted: function(selector) {
        if (selector === undefined) selector = ".phone-formatted";
        $(selector).inputmask({"mask": "99 999-9999"});
    }
};
