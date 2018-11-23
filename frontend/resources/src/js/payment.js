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
                htmlData += '<button class="btn btn-lg full-width btn-' + addonClass + '" type="button" data-id="' + this.users[this.user].groups[i].id + '" onclick="Payment.toggleGroup(this);">' +
                        this.users[this.user].groups[i].name +
                        '<br><small>' + addonText + '</small>' +
                    '</button><div id="payment-' + this.users[this.user].groups[i].id + '" class="group-payments hidden" data-groupid="' + this.users[this.user].groups[i].id + '" data-groupname="' + this.users[this.user].groups[i].name + '"><br><div class="row">';
                if (this.users[this.user].groups[i].debt > 0) {
                    htmlData += '<div class="col-xs-12 col-sm-6 col-md-4"><button class="btn btn-primary full-width" data-sum="' + this.users[this.user].groups[i].debt + '" onclick="Payment.selectSum(this);">' +
                        'Погасить задолженность ' + this.users[this.user].groups[i].debt + ' сум' +
                        '</button></div>';
                }
                htmlData += '<div class="col-xs-12 col-sm-6 col-md-4"><button class="btn btn-default full-width" data-sum="' + this.users[this.user].groups[i].price + '" onclick="Payment.selectSum(this);">' +
                    'за 1 месяц ' + this.users[this.user].groups[i].price + ' сум' +
                    '</button></div>' +

                    '<div class="col-xs-12 col-sm-6 col-md-4"><button class="btn btn-default full-width" data-sum="' + this.users[this.user].groups[i].priceDiscount + '" onclick="Payment.selectSum(this);">' +
                    'за 3 месяца ' + this.users[this.user].groups[i].priceDiscount + ' сум' +
                    '</button></div>' +

                    '<div class="col-xs-12 col-sm-6 col-md-4"><div class="input-group"><input type="number" min="1000" step="1000" class="form-control custom_sum" placeholder="сумма">' +
                    '<span class="input-group-btn"><button class="btn btn-default" data-sum="none" onclick="Payment.selectSum(this);">другая сумма</button></span></div></div>' +
                    '</div></div><hr>';
            }
            $("#group_select").html(htmlData);
        }
    },
    toggleGroup: function(e) {
        $(".group-payments").addClass("hidden");
        $("#payment-" + $(e).data("id")).removeClass("hidden");
    },
    selectSum: function(e) {
        var sum = $(e).data("sum");
        if (sum === 'none') {
            sum = parseInt($(e).closest('.input-group').find('input.custom_sum').val());
        } else {
            sum = parseInt(sum);
        }
        if (sum < 1000) return;

        $("#pupil").data("val", this.user).val(this.users[this.user].name);
        $("#group").data("val", $(e).closest(".group-payments").data("groupid")).val($(e).closest(".group-payments").data("groupname"));
        $("#amount").val(sum);
        $("#payment_form").modal();
    },
    completePayment: function(form) {

    }
};