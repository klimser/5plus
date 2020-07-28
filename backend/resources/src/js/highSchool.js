var HighSchool = {
    deletePhoto: function(e) {
        if (confirm("Вы уверены?")) {
            $.ajax({
                url: $(e).attr("href"),
                dataType: "json",
                success: function(data) {
                    if (data.status === "ok") {
                        var photoElem = $("#highschool_photo");
                        $(photoElem).find("img").remove();
                        $(photoElem).find("a").remove();
                        $(photoElem).append('<div class="text-success"><span class="fas fa-check"></span> Удалено</div>');
                    } else {
                        Main.throwFlashMessage('#messages_place', 'Ошибка: ' + data.message, 'alert-danger');
                    }
                }
            })
        }
        return false;
    }
};
