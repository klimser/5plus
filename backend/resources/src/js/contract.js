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
        pupil.groups.forEach(function(group) {
            blockHtml += '<button class="btn btn-outline-dark btn-lg mr-3 mb-2 group-result-button" type="button" id="group-' + group.id + '" onclick="Contract.setGroup(' + group.id + ', \'' + group.date_start + '\', \'' + group.date_charge_till + '\');">' + Main.groupMap[group.id].name + '</button>';
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
    setGroup: function (groupId, dateStart, dateChargeTill) {
        Money.groupId = groupId;
        $("#groups_block").find("button").removeClass("btn-primary").addClass('btn-outline-dark');
        if ($("#group-" + groupId).length > 0) {
            $("#group-" + groupId).addClass('btn-primary').removeClass('btn-outline-dark');
            $("#group_new_block").removeClass(['bg-primary', 'text-white']).find('button').removeClass('btn-outline-light').addClass('btn-outline-dark');
        } else {
            $("#group_new_block").addClass(['bg-primary', 'text-white']).find('button').addClass('btn-outline-light').removeClass('btn-outline-dark');
        }

        if (dateStart !== undefined) {
            $("#date_start").text(dateStart);
            $("#date_charge_till").text(dateChargeTill);
            $("#group_dates").collapse("show");
        } else {
            $("#group_dates").collapse("hide");
        }
        $("#payment-0").find(".price").text(Main.groupMap[groupId].price);
        $("#payment-1").find(".price").text(Main.groupMap[groupId].price3);
        $("#payment_type_block").collapse("show").find("button").removeClass("btn-primary").addClass('btn-outline-dark');
        $("#income_form").collapse('hide');
    },
    setPayment: function(paymentType) {
        $("#payment_type_block").find("button").removeClass("btn-primary").addClass('btn-outline-dark');
        $("#payment-" + paymentType).addClass('btn-primary').removeClass('btn-outline-dark');
        this.paymentType = paymentType;

        let amountInput = $("#amount");
        let group = Main.groupMap[Money.groupId];
        if (this.paymentType === 1) {
            $(amountInput).val(group.price3).attr("min", group.price3);
        } else {
            $(amountInput).val(group.price).attr("min", 1000);
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
