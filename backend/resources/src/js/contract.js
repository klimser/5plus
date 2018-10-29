var Contract = {
    paymentType: null,
    renderGroupsBlock: function () {
        var pupil = this.pupils[this.pupilId];
        var blockHtml = '<div class="panel panel-default"><div class="panel-body">';
        for (var i = 0; i < pupil.groups.length; i++) {
            blockHtml += '<button class="btn btn-default btn-lg margin-right-10" type="button" id="group-' + pupil.groups[i] + '" onclick="Money.setGroup(' + pupil.groups[i] + ');">' + this.groups[pupil.groups[i]].name + '</button>';
        }
        blockHtml += '<a href="/user/add-to-group?user_id=' + this.pupilId + '" target="_blank" class="btn btn-default btn-lg">Добавить в новую группу <span class="glyphicon glyphicon-new-window"></span></a>';
        blockHtml += '</div></div>';
        $("#groups_block").html(blockHtml);
        if (pupil.groups.length === 1) $("#groups_block").find('button:first').click();
    },
    setGroup: function (groupId) {
        this.groupId = groupId;
        $("#groups_block").find("button").removeClass("btn-primary");
        $("#group-" + groupId).addClass('btn-primary');

        var group = this.groups[this.groupId];
        $("#payment-0").find(".price").text(group.month_price);
        $("#payment-1").find(".price").text(group.month_price_discount);
        $("#date_start").text(group.date_start);
        $("#date_charge_till").text(group.date_charge_till);
        $("#payment_type_block").removeClass("hidden").find("button").removeClass("btn-primary");
    },
    setPayment: function(paymentType) {
        $("#payment_type_block").find("button").removeClass("btn-primary");
        $("#payment-" + paymentType).addClass('btn-primary');
        this.paymentType = paymentType;

        var amountInput = $("#amount");
        if (this.paymentType === 1) {
            $(amountInput).val(Money.groups[Money.groupId].month_price_discount * 3).attr("min", Money.groups[Money.groupId].month_price_discount * 3);
        } else {
            $(amountInput).val(Money.groups[Money.groupId].month_price).attr("min", 1000);
        }
        $("#income_form").removeClass('hidden');
    },
    complete: function(form) {
        if (!Money.pupilId || !Money.groupId || this.paymentType === null) return false;
        $("#user_input").val(Money.pupilId);
        $("#group_input").val(Money.groupId);
        $("#discount_input").val(Money.paymentType);
        return true;
    }
};