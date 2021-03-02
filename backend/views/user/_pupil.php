<?php

use common\components\DefaultValuesComponent;
use common\models\User;
use yii\bootstrap4\Html;

/* @var $this yii\web\View */
?>
<h2>Студент</h2>
<?= Html::radioList(
    'person_type',
    null,
    [
        User::ROLE_PARENTS => 'Физ. лицо',
        User::ROLE_COMPANY => 'Юр. лицо',
    ],
    ['itemOptions' => ['onchange' => 'User.changePersonType();', 'class' => 'person_type', 'required' => true]]
); ?>

<div class="form-group">
    <label for="user-pupil-name">Имя</label>
    <input class="form-control" name="pupil[name]" id="user-pupil-name" maxlength="127" required>
</div>

<div class="form-group">
    <label for="user-pupil-note">Заметки</label>
    <textarea class="form-control" name="pupil[note]" id="user-pupil-note" maxlength="255" rows="3"></textarea>
</div>

<div class="form-group">
    <label for="user-pupil-phoneformatted">Телефон</label>
    <?= str_replace(
        '{input}',
        '<input name="pupil[phoneFormatted]" id="user-pupil-phoneformatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" class="form-control phone-formatted" onchange="User.checkPhone(this);">',
        DefaultValuesComponent::getPhoneInputTemplate()
    ); ?>
</div>

<div class="form-group">
    <label for="user-pupil-phone2formatted">Доп. телефон</label>
    <?= str_replace(
        '{input}',
        '<input name="pupil[phone2Formatted]" id="user-pupil-phone2formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" class="form-control phone-formatted" onchange="User.checkPhone(this);">',
        DefaultValuesComponent::getPhoneInputTemplate()
    ); ?>
</div>
