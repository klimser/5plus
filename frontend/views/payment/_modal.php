<?php

use common\models\Contract;
use yii\bootstrap4\Html;
use yii\helpers\Url;
?>

<div id="payment_form" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <?= Html::beginForm(Url::to(['payment/create']), 'post', ['onsubmit' => 'return false;']); ?>
            <div class="modal-header">
                <h4 class="modal-title">Оплатить</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="message_board"></div>
                <div id="order_form_body" class="form-horizontal">
                    <div class="form-group">
                        <label for="pupil" class="col-4 col-md-3 col-xl-2 control-label">Студент</label>
                        <div class="col-8 col-md-9 col-xl-10">
                            <input class="form-control" id="pupil" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="group" class="col-4 col-md-3 col-xl-2 control-label">Группа</label>
                        <div class="col-8 col-md-9 col-xl-10">
                            <input class="form-control" id="group" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="amount" class="col-4 col-md-3 col-xl-2 control-label">Сумма</label>
                        <div class="col-8 col-md-9 col-xl-10">
                            <input type="number" class="form-control" id="amount" min="1000" step="1000" inputmode="numeric" autocomplete="transaction-amount" required disabled onchange="Payment.checkAmount(this);">
                            <small id="amount-notice" class="form-text text-danger collapse">
                                Внимание! Оплата с повышенной стоимостью занятия. Для оплаты с обычной стоимостью оплачивайте не менее 12 занятий.
                            </small>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-12 text-center">
                            <?php if (Yii::$app->request->getCookies()->get('apelsin_test') == 'mfp45'): ?>
                                <button type="submit" onclick="return Payment.completePayment(this);" data-payment="<?= Contract::PAYMENT_TYPE_APELSIN; ?>" class="btn apelsin_logo pay_button m-2">
                                    Оплатить через <span class="sr-only">Apelsin</span> <i class="ml-2"></i>
                                </button>
                            <?php endif; ?>
                            <button type="submit" onclick="return Payment.completePayment(this);" data-payment="<?= Contract::PAYMENT_TYPE_ATMOS; ?>" class="btn atmos_logo pay_button m-2">
                                Оплатить через <span class="sr-only">ATMOS</span> <i class="ml-2"></i>
                            </button>
                            <button type="submit" onclick="return Payment.completePayment(this);" data-payment="<?= Contract::PAYMENT_TYPE_CLICK; ?>" class="btn click_logo pay_button m-2">
                                Оплатить через <span class="sr-only">CLICK</span> <i class="ml-2"></i>
                            </button>
                            <button type="submit" onclick="return Payment.completePayment(this);" data-payment="<?= Contract::PAYMENT_TYPE_PAYME; ?>" class="btn payme_logo pay_button m-2">
                                Оплатить через <span class="sr-only">Payme</span> <i class="ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?= Html::endForm(); ?>
        </div>
    </div>
</div>
