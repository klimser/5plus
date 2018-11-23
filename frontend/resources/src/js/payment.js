var Payment = {
    users: {},
    user: null,
    selectPupil: function(e) {
        this.user = $(e).data("pupil");
        $("#user_select").find("button.pupil-button").removeClass('btn-primary').addClass('btn-default');
        $(e).addClass('btn-primary').removeClass('btn-default');
        this.renderGroupSelect();
    },
    renderGroupSelect: function() {
        if (this.user !== null && this.users.hasOwnProperty(this.user)) {
            var htmlData = '';
            for (var i = 0; i < this.users[this.user].groups.length; i++) {
                var addonClass = 'default';
                var addonText = '';
                if (this.users[this.user].groups[i].debt > 0) {
                    addonClass = 'danger';
                    addonText = 'задолженность ' + this.users[this.user].groups[i].debt + ' сум';
                } else {
                    addonText = 'оплачено до ' + this.users[this.user].groups[i].paid;
                }
                htmlData += '<button class="btn btn-lg btn-' + addonClass + '" type="button" data-id="' + this.users[this.user].groups[i].id + '" onclick="Payment.toggleGroup(this);">' +
                        this.users[this.user].groups[i].name +
                        '<br><small>' + addonText + '</small>' +
                    '</button><div id="payment-' + this.users[this.user].groups[i].id + '" class="group-payments hidden" data-groupId="' + this.users[this.user].groups[i].id + '" data-groupName="' + this.users[this.user].groups[i].name + '">';
                if (this.users[this.user].groups[i].debt > 0) {
                    htmlData += '<button class="btn btn-primary" data-sum="' + this.users[this.user].groups[i].debt + '" onclick="Payment.selectSum(this);">' +
                        'Погасить задолженность ' + this.users[this.user].groups[i].debt + ' сум' +
                        '</button>';
                }
                htmlData += '<button class="btn btn-default" data-sum="' + this.users[this.user].groups[i].price + '" onclick="Payment.selectSum(this);">' +
                    'за 1 месяц ' + this.users[this.user].groups[i].price + ' сум' +
                    '</button>' +

                    '<button class="btn btn-default" data-sum="' + this.users[this.user].groups[i].priceDiscount + '" onclick="Payment.selectSum(this);">' +
                    'за 3 месяца ' + this.users[this.user].groups[i].priceDiscount + ' сум' +
                    '</button>' +

                    '<button class="btn btn-default" data-sum="none" onclick="Payment.selectSum(this);">другая сумма</button></div>';
            }
            $("#group_select").html(htmlData);
        }
    },
    toggleGroup: function(e) {
        $(".group-payments").addClass("hidden");
        $("#payment-" + $(e).data("id")).removeClass("hidden");
    },
    selectSum: function(e) {
        $("#pupil").data("val", this.user).val(this.users[this.user].name);
        $("#group").data("val", $(e).closest(".group-payments").data("groupId")).val($(e).closest(".group-payments").data("groupName"));
        $("#amount").val($(e).data("sum"));
    }
};