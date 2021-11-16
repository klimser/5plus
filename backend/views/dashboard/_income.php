<?php

use common\models\Contract;

?>
<div id="modal-income" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="income-form" onsubmit="Dashboard.completeIncome(this); return false;">
                <div class="modal-header">
                    <h4 class="modal-title">Принять оплату</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="income-messages-place"></div>
                    <input type="hidden" name="income[userId]" id="income-user-id" required>
                    <input type="hidden" name="income[groupId]" id="income-group-id" required>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="income-pupil-name">Студент</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="income-pupil-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="income-group-name">Группа</label>
                        <div class="col-12 col-sm-9">
                            <input readonly class="form-control-plaintext" id="income-group-name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label" for="income-amount">Сумма</label>
                        <div class="col-12 col-sm-9">
                            <input  id="income-amount" class="form-control income-amount" name="income[amount]" type="number" min="1000" step="1000" placeholder="Сумма оплаты" autocomplete="off" required onchange="Dashboard.checkAmount(this);">
                            <small id="amount-notice" class="form-text text-danger collapse">
                                Внимание! Оплата с повышенной стоимостью занятия. Для оплаты с обычной стоимостью оплачивайте не менее 12 занятий.
                            </small>
                            <div class="amount-helper-buttons">
                                <button type="button" class="btn btn-outline-secondary btn-sm price-lesson" onclick="Dashboard.setAmount(this);">за 1 занятие</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm price-month" onclick="Dashboard.setAmount(this);">за 1 месяц</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-12 col-sm-3 control-label">Способ оплаты</label>
                        <div class="col-12 col-sm-9">
                            <?php foreach (Contract::MANUAL_PAYMENT_TYPES as $key => $paymentMethod): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="income[payment_type]" id="payment_type_<?= $key; ?>" value="<?= $paymentMethod; ?>" required>
                                    <label class="form-check-label" for="payment_type_<?= $key; ?>"><?= Contract::PAYMENT_TYPE_LABELS[$paymentMethod]; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="payment_comment" class="col-12 col-sm-3 control-label">Комментарий к платежу</label>
                        <div class="col-12 col-sm-9">
                            <input id="payment_comment" name="income[comment]" class="form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                    <button class="btn btn-primary" id="income-button">принять</button>
                </div>
            </form>
        </div>
    </div>
</div>
