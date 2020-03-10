let Dashboard = {
    step1: function() {
        $(".step2").addClass("hidden");
        $("#step1").removeClass("hidden");
    },
    step2: function(subclass) {
        $("#step1").addClass("hidden");
        $(".step2").addClass("hidden");
        let target = $("#step2_" + subclass);
        $(target).removeClass("hidden");
        let focusable = $(target).find(".autofocus");
        if (focusable.length) {
            $(focusable).focus();
        }
    }
};
