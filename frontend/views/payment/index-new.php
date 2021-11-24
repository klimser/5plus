<?php

use common\models\Contract;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */
/* @var $giftCardTypes \common\models\GiftCardType[] */

$this->params['breadcrumbs'][] = 'Онлайн оплата для новых студентов'; ?>

<div class="container">
    <div class="content-box payment-panel">
        <?= Html::beginForm(Url::to(['payment/create-new']), 'post', ['onsubmit' => 'return false;']); ?>
            <div class="alert alert-info">
                <b>Внимание!</b><br>
                Указывайте свои данные корректно и полностью, они будут указаны в договоре между Вами и учебным центром.
            </div>
            <div class="row">
                <div class="col-12 col-lg-6">
                    <div class="form-group required">
                        <label for="giftcard-pupil-name">Фамилия, имя, отчество студента</label>
                        <input name="giftcard[pupil_name]" id="giftcard-pupil-name" class="form-control" maxlength="127" autocomplete="name" required>
                    </div>
                    <div class="form-group required">
                        <label for="giftcard-pupil-phone">Телефон студента</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">+998</span>
                            </div>
                            <input type="tel" name="giftcard[pupil_phone]" id="giftcard-pupil-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" inputmode="numeric" autocomplete="tel-national" required>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="form-group">
                        <label for="giftcard-parents-name">Фамилия, имя, отчество родителей</label>
                        <input name="giftcard[parents_name]" id="giftcard-parents-name" class="form-control" maxlength="127" autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label for="giftcard-parents-phone">Телефон родителей</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">+998</span>
                            </div>
                            <input type="tel" name="giftcard[parents_phone]" id="giftcard-parents-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" inputmode="numeric" autocomplete="tel-national">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group required">
                <label for="giftcard-type">Предмет</label>
                <select name="giftcard[type]" id="giftcard-type" class="form-control" required>
                    <?php foreach ($giftCardTypes as $giftCardType): ?>
                        <option value="<?= $giftCardType->id; ?>" data-amount="<?= $giftCardType->amount; ?>">
                            <?= $giftCardType->name . ' (' . $giftCardType->amount . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group required">
                <label for="giftcard-email">E-mail для отправки квитанции об оплате</label>
                <input type="email" name="giftcard[email]" id="giftcard-email" class="form-control" maxlength="255" inputmode="email" autocomplete="email" required>
            </div>
            <div class="alert alert-info">
                Квитанция об оплате со штрих-кодом будет выслана на указанный вами e-mail. Вам необходимо будет показать квитанцию на телефоне или в распечатанном виде Вашим заботливым менеджерам в администрации «Пять с Плюсом» для допуска к занятиям.<br>
                Желаем вам хорошего настроения и шикарной учебы!
            </div>
            <div class="form-group form-check">
                <input class="form-check-input" type="checkbox" value="1" id="agreement" required>
                <label class="form-check-label" for="agreement">
                    С <a href="<?= Yii::$app->homeUrl; ?>uploads/images/legal_documents/public_offer.pdf" target="_blank">публичной офертой</a> ознакомлен(а) и согласен(на)
                </label>
            </div>
            <div id="message_board"></div>
            <div class="text-right mw-100">
                <button type="submit" onclick="return Payment.completePayment(this);" data-payment="<?= Contract::PAYMENT_TYPE_APELSIN; ?>" class="btn apelsin_logo pay_button m-2">
                    Оплатить через <span class="sr-only">Apelsin</span> <i class="ml-2"></i>
                </button>
                <button type="submit" onclick="return Payment.completeNewPayment(this);" data-payment="<?= Contract::PAYMENT_TYPE_ATMOS; ?>" class="btn atmos_logo pay_button m-2">
                    Оплатить через <span class="sr-only">Atmos</span> <i class="ml-2"></i>
                </button>
                <button type="submit" onclick="return Payment.completeNewPayment(this);" data-payment="<?= Contract::PAYMENT_TYPE_CLICK; ?>" class="btn click_logo pay_button m-2">
                    Оплатить через <span class="sr-only">CLICK</span> <i class="ml-2"></i>
                </button>
                <button type="submit" onclick="return Payment.completeNewPayment(this);" data-payment="<?= Contract::PAYMENT_TYPE_PAYME; ?>" class="btn payme_logo pay_button m-2">
                    Оплатить через <span class="sr-only">Payme</span> <i class="ml-2"></i>
                </button>
            </div>
        <?= Html::endForm(); ?>
    </div>
</div>
