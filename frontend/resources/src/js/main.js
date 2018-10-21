var Main = {
    throwFlashMessage: function (blockSelector, message, additionalClass, append) {
        if (typeof append !== 'boolean') append = false;
        var blockContent = '<div class="alert alert-dismissible ' + additionalClass + '"><button type="button" class="close" data-dismiss="alert" aria-label="Закрыть"><span>&times;</span></button>' + message + '</div>';
        if (append) $(blockSelector).append(blockContent);
        else $(blockSelector).html(blockContent);
    },
    loadMore: function (e, container, item) {
        if ($(container).length === 0) return false;
        if (this.loadMoreProcess === undefined || this.loadMoreProcess === false) {
            this.loadMoreProcess = true;
            this.loadMoreContainer = container;
            var loaded = $(container).find(item).length;
            $.ajax({
                url: $(e).attr("href"),
                type: "get",
                 dataType: "html",
                data: {loaded: loaded},
                success: function (data) {
                    Main.loadMoreProcess = false;
                    if (data.length > 0) $(Main.loadMoreContainer).append(data);
                    else $(".more-block").addClass("hidden");
                },
                error: function (xhr, textStatus, errorThrown) {
                    Main.loadMoreProcess = false;
                }
            });
        }
        return false;
    }
};