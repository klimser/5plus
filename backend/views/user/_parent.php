<?php

use common\components\DefaultValuesComponent;
use common\models\User;
use yii\bootstrap4\Html;

/* @var $this yii\web\View */
?>

<div id="parents_block" class="parent-edit-block col-12 col-md-6 collapse show">
    <h2>Родители</h2>

    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input" type="radio" name="parent_type" value="none" onclick="Dashboard.changeParentType(this)">
            Студент уже взрослый
        </label>
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input" type="radio" name="parent_type" value="exist" onclick="Dashboard.changeParentType(this)">
            Есть брат (сестра), выбрать родителей из списка
        </label>
    </div>
    <div class="parent-edit-option parent-edit-exist collapse my-3">
        <input type="hidden" class="autocomplete-user-id" name="parent[id]">
        <input class="autocomplete-user form-control" placeholder="начните печатать фамилию или имя" required disabled data-role="<?= User::ROLE_PARENTS; ?>">
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input" type="radio" name="parent_type" value="new" onclick="Dashboard.changeParentType(this)" checked>
            Добавить родителей
        </label>
    </div>
    <div class="parent-edit-option parent-edit-new collapse show my-3">
        <div class="form-group">
            <label for="user-parent-name">Имя</label>
            <input class="form-control" name="parent[name]" id="user-parent-name" maxlength="127" required>
        </div>

        <div class="form-group">
            <label for="user-parent-phoneformatted">Телефон</label>
            <?= str_replace(
                '{input}',
                '<input name="parent[phoneFormatted]" id="user-parent-phoneformatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" class="form-control phone-formatted" onchange="User.checkPhone(this);" required>',
                DefaultValuesComponent::getPhoneInputTemplate()
            ); ?>
        </div>

        <div class="form-group">
            <label for="user-parent-phone2formatted">Доп. телефон</label>
            <?= str_replace(
                '{input}',
                '<input name="parent[phone2Formatted]" id="user-parent-phone2formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" class="form-control phone-formatted" onchange="User.checkPhone(this);">',
                DefaultValuesComponent::getPhoneInputTemplate()
            ); ?>
        </div>
    </div>
</div>

<div id="company_block" class="parent-edit-block col-12 col-md-6 collapse">
    <h2>Компания</h2>

    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input" type="radio" name="company_type" value="exist" onclick="Dashboard.changeParentType(this, 'company_type')">
            Выбрать компанию
        </label>
    </div>
    <div class="parent-edit-option parent-edit-exist collapse">
        <input type="hidden" class="autocomplete-user-id" name="company[id]">
        <input class="autocomplete-user form-control" placeholder="начните печатать название" required disabled data-role="<?= User::ROLE_COMPANY; ?>">
    </div>
    <div class="form-check">
        <label class="form-check-label">
            <input class="form-check-input" type="radio" name="company_type" value="new" onclick="Dashboard.changeParentType(this, 'company_type')" checked>
            Добавить компанию
        </label>
    </div>

    <div class="parent-edit-option parent-edit-new collapse show">
        <div class="form-group">
            <label for="user-company-name">Имя</label>
            <input class="form-control" name="company[name]" id="user-company-name" maxlength="127" required disabled>
        </div>

        <div class="form-group">
            <label for="user-company-phoneformatted">Телефон</label>
            <?= str_replace(
                '{input}',
                '<input name="company[phoneFormatted]" id="user-company-phoneformatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" class="form-control phone-formatted" onchange="User.checkPhone(this);" required disabled>',
                DefaultValuesComponent::getPhoneInputTemplate()
            ); ?>
        </div>

        <div class="form-group">
            <label for="user-company-phone2formatted">Доп. телефон</label>
            <?= str_replace(
                '{input}',
                '<input name="company[phone2Formatted]" id="user-company-phone2formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" class="form-control phone-formatted" onchange="User.checkPhone(this);" disabled>',
                DefaultValuesComponent::getPhoneInputTemplate()
            ); ?>
        </div>
    </div>
</div>
