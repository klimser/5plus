let Contract = {
    paymentType: null,
    setPupil: function (pupilId) {
        Money.pupilId = pupilId;
        $("#pupils_block").find("button").removeClass("btn-primary");
        $("#pupil-" + pupilId).addClass('btn-primary');
        this.renderGroupsBlock();
    },
    renderGroupsBlock: function () {
        let pupil = Money.pupils[Money.pupilId];
        let blockHtml = '<div class="panel panel-default"><div class="panel-body">';
        pupil.groups.forEach(function(groupId) {
            blockHtml += '<button class="contract-group-btn btn btn-default btn-lg margin-right-10" type="button" id="group-' + groupId + '" onclick="Contract.setGroup(' + groupId + ');">' + Money.groups[groupId].name + '</button>';
        });
        blockHtml += '<div class="col-xs-12 col-sm-6 col-md-3"><label for="new_group">Ещё не занимается, просто выдать договор:</label><br>' +
            '<select id="new_group" class="form-control">';
        Main.groupList.forEach(function(group) {
            if (pupil.groups.indexOf(group.id) < 0) {
                blockHtml += '<option value="' + group.id + '">' + group.name + '</option>';
            }
        });
        blockHtml += '</select><br>' +
            '<button type="button" class="btn btn-default" id="new_group_button" onclick="Contract.setGroup(parseInt($(\'#new_group\').val()));">Выбрать</button></div>';
        blockHtml += '</div></div>';
        $("#groups_block").html(blockHtml);
        if (pupil.groups.length === 1) $("#groups_block").find('.contract-group-btn:first').click();
    },
    getGroup: function(groupId) {
        if (groupId in Money.groups) {
            return Money.groups[groupId];
        } else {
            for (let i = 0; i < Main.groupList.length; i++) {
                if (Main.groupList[i].id === groupId) {
                    return Main.groupList[i];
                }
            }
        }
        return null;
    },
    setGroup: function (groupId) {
        Money.groupId = groupId;
        $("#groups_block").find("button").removeClass("btn-primary");
        if ($("#group-" + groupId).length > 0) {
            $("#group-" + groupId).addClass('btn-primary');
        } else {
            $("#new_group_button").addClass('btn-primary');
        }

        let group = this.getGroup(groupId);
        if (group.hasOwnProperty("date_start")) {
            $("#date_start").text(group.date_start);
            $("#date_charge_till").text(group.date_charge_till);
            $("#group_dates").removeClass("hidden");
        } else {
            $("#group_dates").addClass("hidden");
        }
        $("#payment-0").find(".price").text(group.month_price);
        $("#payment-1").find(".price").text(group.discount_price);
        $("#payment_type_block").removeClass("hidden").find("button").removeClass("btn-primary");
        $("#income_form").addClass('hidden');
    },
    setPayment: function(paymentType) {
        $("#payment_type_block").find("button").removeClass("btn-primary");
        $("#payment-" + paymentType).addClass('btn-primary');
        this.paymentType = paymentType;

        let amountInput = $("#amount");
        let group = this.getGroup(Money.groupId);
        if (this.paymentType === 1) {
            $(amountInput).val(group.discount_price).attr("min", group.discount_price);
        } else {
            $(amountInput).val(group.month_price).attr("min", 1000);
        }
        $("#income_form").removeClass('hidden');
    },
    complete: function(form) {
        if (!Money.pupilId || !Money.groupId || this.paymentType === null) return false;
        $("#user_input").val(Money.pupilId);
        $("#group_input").val(Money.groupId);
        $("#discount_input").val(Money.paymentType);
        return true;
    },
    init: function() {
        Money.className = 'Contract';
        Main.loadGroups();
    }
};
