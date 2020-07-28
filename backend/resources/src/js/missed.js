let Missed = {
    showCall: function(groupPupilId, button) {
        $(button).remove();
        $("#phone_call_" + groupPupilId).html(this.renderCallForm(groupPupilId));
        return false;
    },
    renderCallForm: function(groupPupilId) {
        return '<form action="/missed/call" method="post">' +
            '<input type="hidden" name="' + $("meta[name='csrf-param']").attr('content') + '" value="' + $("meta[name='csrf-token']").attr('content') + '">' +
            '<input type="hidden" name="groupPupil" value="' + groupPupilId + '">' +
            '<b>Результат звонка</b>' +
            '<div class="form-check">' +
            '<label class="form-check-label"><input class="form-check-input" type="radio" name="callResult[' + groupPupilId + ']" value="fail" required> Недозвон</label>' +
            '</div>' +
            '<div class="form-check">' +
            '<label class="form-check-label"><input class="form-check-input" type="radio" name="callResult[' + groupPupilId + ']" value="phone" required> Неправильный номер телефона</label>' +
            '</div>' +
            '<div class="form-check">' +
            '<label class="form-check-label"><input class="form-check-input" type="radio" name="callResult[' + groupPupilId + ']" value="other" required> Другое</label>' +
            '</div>' +
            '<textarea class="form-control" rows="3" name="callComment[' + groupPupilId + ']" placeholder="Напишите сюда результат звонка"></textarea>' +
            '<button class="btn btn-primary my-2">Сохранить</button>' +
            '</form>';
    }
};
