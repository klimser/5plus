let Missed = {
    showCall: function(courseStudentId, button) {
        $(button).remove();
        $("#phone_call_" + courseStudentId).html(this.renderCallForm(courseStudentId));
        return false;
    },
    renderCallForm: function(courseStudentId) {
        return '<form action="/missed/call" method="post">' +
            '<input type="hidden" name="' + $("meta[name='csrf-param']").attr('content') + '" value="' + $("meta[name='csrf-token']").attr('content') + '">' +
            '<input type="hidden" name="courseStudent" value="' + courseStudentId + '">' +
            '<b>Результат звонка</b>' +
            '<div class="form-check">' +
            '<label class="form-check-label"><input class="form-check-input" type="radio" name="callResult[' + courseStudentId + ']" value="fail" required> Недозвон</label>' +
            '</div>' +
            '<div class="form-check">' +
            '<label class="form-check-label"><input class="form-check-input" type="radio" name="callResult[' + courseStudentId + ']" value="phone" required> Неправильный номер телефона</label>' +
            '</div>' +
            '<div class="form-check">' +
            '<label class="form-check-label"><input class="form-check-input" type="radio" name="callResult[' + courseStudentId + ']" value="other" required> Другое</label>' +
            '</div>' +
            '<textarea class="form-control" rows="3" name="callComment[' + courseStudentId + ']" placeholder="Напишите сюда результат звонка"></textarea>' +
            '<button class="btn btn-primary my-2">Сохранить</button>' +
            '</form>';
    }
};
