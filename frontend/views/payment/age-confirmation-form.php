<?php

use common\components\helpers\Html;
use common\models\User;
use himiklab\yii2\recaptcha\ReCaptcha2;
use yii\helpers\Url;

/* @var $this \frontend\components\extended\View */
/* @var $user User|null */
/* @var $phone string|null */
/* @var $webpage \common\models\Webpage */

$this->params['breadcrumbs'][] = ['url' => Url::to(['webpage', 'id' => $webpage->id]), 'label' => 'Онлайн оплата'];
$this->params['breadcrumbs'][] = 'Подтвердите свой созраст';
?>

<div class="container">
    <div class="content-box">
        <h1>Подтвердите свой созраст</h1>
        <div class="row">
            <div id="age-confirmation-form" class="col-12">
                <?= $form = Html::beginForm('', 'post', ['onsubmit' => 'return AgeConfirmation.submit(this);']); ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="agree" name="agree" autocomplete="off" required onchange="AgeConfirmation.agreeClick(this);">
                        <label class="form-check-label" for="agree">
                            Подтверждаю, что ознакомлен(-а) и согласен(-на) с <a href="<?= Yii::$app->homeUrl; ?>uploads/images/legal_documents/public_offer.pdf" target="_blank">Публичной офертой</a> и подтверждаю, что мне исполнилось 18 лет.
                        </label>
                    </div>    
                    <div id="confirmation-block" class="collapse">
                        <div id="messages_place"></div>
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="phone">Телефон</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+998</span>
                                        </div>
                                        <input type="tel" name="phoneFormatted" id="phone" class="form-control phone-formatted <?= $phone ? ' form-control-plaintext ' : ''; ?>" maxlength="11" pattern="\d{2} \d{3}-\d{4}" inputmode="numeric" autocomplete="tel-national" required data-type="<?= $phone ? 'session' : 'manual'; ?>" <?= $phone ? ' readonly ' : ''; ?>>
                                        <?php if ($phone): ?>
                                            <div class="input-group-append">
                                                <button class="btn btn-light" type="button" onclick="AgeConfirmation.flushPhoneNumber(this);"><span class="fas fa-times-circle"></span></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="sms-code">Код подтверждения</label>
                                    <input class="form-control" id="sms-code" aria-describedby="sms-code-helper" name="smsCode" required>
                                    <small id="sms-code-helper" class="form-text text-muted">Код из СМС.</small>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 align-self-end">
                                <div class="form-group">
                                    <?= ReCaptcha2::widget(['name' => 'reCaptcha']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="button" id="send-sms" class="btn btn-outline-primary" onclick="AgeConfirmation.triggerSms(this);">Отправить СМС</button>
                            <button id="submit-button" class="btn btn-primary ml-3">Подтвердить</button>
                        </div>
                    </div>
                <?= Html::endForm(); ?>
            </div>
        </div>
    </div>
</div>

