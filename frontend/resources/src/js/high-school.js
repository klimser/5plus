var HighSchool = {
    showFullDescr: function (e) {
        var block = $(e).parent().find(".highschool_descr_full");
        if (block.hasClass("hidden")) {
            $(".highschool_descr_full:not(.hidden)").each(function() {
                $(this).addClass("hidden");
                $(this).parent().find('button.highschool_more_btn').text('подробнее');
            });
            block.removeClass("hidden");
            $(e).text('свернуть');
        } else {
            block.addClass("hidden");
            $(e).text('подробнее');
        }
    },
    init: function() {
        $(document.body).scrollspy({target: ".toc"});

        $(window).on("load", function() {
            $(document.body).scrollspy("refresh")
        });

        $('.toc [href="#"]').click(function(event){event.preventDefault();});
        setTimeout(function() {
            var elem = $(".toc");
            elem.affix({
                offset:{
                    top: function() {
                        return this.top = elem.offset().top;
                    },
                    bottom: function() {
                        return this.bottom = $(".bottom_menu").outerHeight() + $(".footer").outerHeight();
                    }
                }
            });
        }, 100);
    }
};