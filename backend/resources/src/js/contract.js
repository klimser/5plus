var Contract = {
    groups: [],
    paymentType: null,
    loadGroups: function () {
        $.ajax({
            url: '/group/list-json',
            data: {},
            dataType: 'json',
            success: function (data) {
                Contract.groups = [];
                if (data.length > 0) {
                    if (!Array.isArray(data)) data = [data];
                    Contract.groups = data;
                }
            }
        });
    },
    setPupil: function (pupilId) {
        Money.pupilId = pupilId;
        $("#pupils_block").find("button").removeClass("btn-primary");
        $("#pupil-" + pupilId).addClass('btn-primary');
        this.renderGroupsBlock();
    },
    renderGroupsBlock: function () {
        var pupil = Money.pupils[Money.pupilId];
        var blockHtml = '<div class="panel panel-default"><div class="panel-body">';
        for (var i = 0; i < pupil.groups.length; i++) {
            blockHtml += '<button class="btn btn-default btn-lg margin-right-10" type="button" id="group-' + pupil.groups[i] + '" onclick="Contract.setGroup(' + pupil.groups[i] + ');">' + Money.groups[pupil.groups[i]].name + '</button>';
        }
        blockHtml += '<div class="col-xs-12 col-sm-6 col-md-3"><label for="new_group">Ещё не занимается, просто выдать договор:</label><br>' +
            '<select id="new_group" class="form-control">';
        for (i = 0; i < this.groups.length; i++) {
            if (pupil.groups.indexOf(this.groups[i].id) < 0) {
                blockHtml += '<option value="' + this.groups[i].id + '">' + this.groups[i].name + '</option>';
            }
        }
        blockHtml += '</select><br>' +
            '<button type="button" class="btn btn-default" onclick="Contract.setGroup(parseInt($(\'#new_group\').val()));">Выбрать</button></div>';
        blockHtml += '</div></div>';
        $("#groups_block").html(blockHtml);
        if (pupil.groups.length === 1) $("#groups_block").find('button:first').click();
    },
    getGroup: function(groupId) {
        var group = null;
        if (groupId in Money.groups) {
            group = Money.groups[groupId];
        } else {
            for (var i = 0; i < this.groups.length; i++) {
                if (this.groups[i].id === groupId) {
                    group = this.groups[i];
                    break;
                }
            }
        }
        return group;
    },
    setGroup: function (groupId) {
        Money.groupId = groupId;
        $("#groups_block").find("button").removeClass("btn-primary");
        $("#group-" + groupId).addClass('btn-primary');

        var group = this.getGroup(groupId);
        if (group.hasOwnProperty("date_start")) {
            $("#date_start").text(group.date_start);
            $("#date_charge_till").text(group.date_charge_till);
            $("#group_dates").removeClass("hidden");
        } else {
            $("#group_dates").addClass("hidden");
        }
        $("#payment-0").find(".price").text(group.month_price);
        $("#payment-1").find(".price").text(group.month_price_discount);
        $("#payment_type_block").removeClass("hidden").find("button").removeClass("btn-primary");
    },
    setPayment: function(paymentType) {
        $("#payment_type_block").find("button").removeClass("btn-primary");
        $("#payment-" + paymentType).addClass('btn-primary');
        this.paymentType = paymentType;

        var amountInput = $("#amount");
        var group = this.getGroup(Money.groupId);
        if (this.paymentType === 1) {
            $(amountInput).val(group.month_price_discount * 3).attr("min", group.month_price_discount * 3);
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
    }
};