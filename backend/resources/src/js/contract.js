let Contract = {
    paymentType: null,
    init: function() {
        Money.className = 'Contract';
        Money.init();
    },
    setPupil: function (pupilId) {
        Money.pupilId = pupilId;
        $(".pupil-result-button").removeClass("btn-primary").addClass('btn-outline-dark');
        $("#pupil-" + pupilId).addClass('btn-primary').removeClass('btn-outline-dark');
        this.renderGroupsBlock();
    },
    renderGroupsBlock: function () {
        let pupil = Money.pupils[Money.pupilId];
        let blockHtml = '<div class="panel panel-default"><div class="panel-body">';
        pupil.groups.forEach(function(groupId) {
            blockHtml += '<button class="btn btn-outline-dark btn-lg mr-3 mb-2 group-result-button" type="button" id="group-' + groupId + '" onclick="Contract.setGroup(' + groupId + ');">' + Money.groups[groupId].name + '</button>';
        });
        blockHtml += '<div class="card group-result-button" id="group_new_block"><div class="card-body"><div class="form-inline"><label for="new_group" class="mr-sm-2">Ещё не занимается, просто выдать договор:</label>' +
            '<select id="new_group" class="form-control mr-sm-2">';
        Main.groupActiveList.forEach(function(groupId) {
            if (pupil.groups.indexOf(groupId) < 0) {
                blockHtml += '<option value="' + groupId + '">' + Main.groupMap[groupId].name + '</option>';
            }
        });
        blockHtml += '</select>' +
            '<button type="button" class="btn btn-outline-dark" id="new_group_button" onclick="Contract.setGroup(parseInt($(\'#new_group\').val()));">Выбрать</button></div></div></div>';
        $("#groups_result").html(blockHtml);
        $('#groups_block').collapse('show');
        if (pupil.groups.length === 1) $("#groups_result").find('.contract-group-btn:first').click();
    },
    getGroup: function(groupId) {
        if (groupId in Money.groups) {
            return Money.groups[groupId];
        }
        
        return Main.groupMap[groupId];
    },
    setGroup: function (groupId) {
        Money.groupId = groupId;
        $("#groups_block").find("button").removeClass("btn-primary").addClass('btn-outline-dark');
        if ($("#group-" + groupId).length > 0) {
            $("#group-" + groupId).addClass('btn-primary').removeClass('btn-outline-dark');
            $("#group_new_block").removeClass(['bg-primary', 'text-white']).find('button').removeClass('btn-outline-light').addClass('btn-outline-dark');
        } else {
            $("#group_new_block").addClass(['bg-primary', 'text-white']).find('button').addClass('btn-outline-light').removeClass('btn-outline-dark');
        }

        let group = this.getGroup(groupId);
        if (group.hasOwnProperty("date_start")) {
            $("#date_start").text(group.date_start);
            if (group.hasOwnProperty("date_charge_till")) {
                $("#date_charge_till").text(group.date_charge_till);
            }
            $("#group_dates").collapse("show");
        } else {
            $("#group_dates").collapse("hide");
        }
        $("#payment-0").find(".price").text(group.month_price);
        $("#payment-1").find(".price").text(group.discount_price);
        $("#payment_type_block").collapse("show").find("button").removeClass("btn-primary").addClass('btn-outline-dark');
        $("#income_form").collapse('hide');
    },
    setPayment: function(paymentType) {
        $("#payment_type_block").find("button").removeClass("btn-primary").addClass('btn-outline-dark');
        $("#payment-" + paymentType).addClass('btn-primary').removeClass('btn-outline-dark');
        this.paymentType = paymentType;

        let amountInput = $("#amount");
        let group = this.getGroup(Money.groupId);
        console.log(group);
        if (this.paymentType === 1) {
            $(amountInput).val(group.discount_price).attr("min", group.discount_price);
        } else {
            $(amountInput).val(group.month_price).attr("min", 1000);
        }
        $("#income_form").collapse('show');
    },
    complete: function(form) {
        if (!Money.pupilId || !Money.groupId || this.paymentType === null) return false;
        $("#user_input").val(Money.pupilId);
        $("#group_input").val(Money.groupId);
        $("#discount_input").val(Money.paymentType);
        return true;
    }
};
