let Dashboard = {
    step1: function() {
        $("#result").addClass("hidden");
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
    },
    find: function(form) {
        let elem = $(form).find(".search");
        let data = {
            value: $(elem).val(),
            type: $(elem).data("search")
        };
        $(".step2 button").prop("disabled", true);
        $.get("/dashboard/find", data, null, 'html')
            .done(function(content) {
                $("#result").html(content).removeClass("hidden");
            })
            .fail(Main.logAndFlashAjaxError)
            .always(function() {
                $(".step2 button").prop("disabled", false);
            });
    },
    showContractForm: function(e) {
        let form = $("#contract-form");
        let data = $(e).data();
        Object.getOwnPropertyNames(data).forEach(function (key) {
            let elem = $(form).find("#contract-" + key);
            if (elem.length > 0) {
                if ($(elem).prop("tagName") === 'P') {
                    $(elem).text(data[key]);
                } else {
                    $(elem).val(data[key]);
                }
            }
        });
        let pupilExistsBlock = $(form).find("#contract-pupil-exists");
        let pupilNewBlock = $(form).find("#contract-pupil-new");
        $(form).find("#contract-createDate").prop("disabled", data.groupPupil > 0);
        if (data.groupPupil > 0) {
            $(pupilExistsBlock).removeClass("hidden");
            $(pupilNewBlock).addClass("hidden");
        } else {
            $(pupilExistsBlock).addClass("hidden");
            $(pupilNewBlock).removeClass("hidden");
            let datepickerOptions = Main.datepickerDefaultSettings;
            datepickerOptions.startDate = data.groupDateStart;
            $($(pupilNewBlock).find(".datepicker")).datepicker(datepickerOptions);
        }
        $('#modal-contract').modal("show");
    },
    completeContract: function(form) {
        Money.completeContract(form)
            .done(function() {
                $("#modal-contract").modal("hide");
                $("#step2_strict form").submit();
            });
    }
};
