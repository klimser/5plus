var Contract = {
    paymentType: null,
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