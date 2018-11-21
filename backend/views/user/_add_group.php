<?php
/* @var $groups \common\models\Group[] */
/* @var $groupData array */
/* @var $paymentData array */
/* @var $contractData array */

/** @var bool $addGroup */
$addGroup = array_key_exists('add', $groupData) && $groupData['add'];
$addPayment = array_key_exists('add', $paymentData) && $paymentData['add'];
$addContract = array_key_exists('add', $contractData) && $contractData['add'];

$getGroupOptionsList = function(int $selectedValue) use ($groups): string {
    $list = '';
    foreach ($groups as $group) {
        $list .= "<option value=\"{$group->id}\" " . ($selectedValue == $group->id ? 'selected' : '')
            . " data-discount=\"{$group->price3Month}\">{$group->name} (с {$group->startDateObject->format('d.m.Y')}"
            . ($group->endDateObject ? "по {$group->endDateObject->format('d.m.Y')}" : '')
            . ") {$group->price3Month} за 3 месяца</option>";
    }
    return $list;
};
?>
<div class="checkbox">
    <label>
        <input type="checkbox" value="1" name="group[add]" onchange="User.checkAddGroup(this);" <?= $addGroup ? 'checked' : ''; ?> autocomplete="off">
        Добавить в группу
    </label>
</div>

<div id="add_group" <?= $addGroup ? '' : 'class="hidden"'; ?>>
    <div class="form-group">
        <label for="group">Группа</label>
        <select class="form-control" id="group" name="group[id]">
            <?= $getGroupOptionsList(array_key_exists('id', $groupData) ? intval($groupData['id']) : 0); ?>
        </select>
    </div>
    <div class="form-group">
        <label for="group_date_from">Начало занятий</label>
        <?= \dosamigos\datepicker\DatePicker::widget([
            'name' => 'group[date_from]',
            'value' => array_key_exists('date_from', $groupData) ? $groupData['date_from'] : date('d.m.Y'),
            'options' => ['id' => 'group_date_from', 'required' => true, 'disabled' => !$addGroup],
            'clientOptions' => [
                'autoclose' => true,
                'format' => 'dd.mm.yyyy',
                'language' => 'ru',
                'weekStart' => 1,
            ]
        ]);?>
    </div>
</div>

<div class="row">
    <div class="col-xs-12 col-sm-6">
        <div class="checkbox">
            <label>
                <input type="checkbox" value="1" name="payment[add]" id="add_payment_switch" onchange="User.checkAddPayment(this);" <?= $addPayment ? 'checked' : ($addGroup ? '' :  'disabled'); ?> autocomplete="off">
                Внести платёж
            </label>
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="checkbox">
            <label>
                <input type="checkbox" value="1" name="contract[add]" id="add_contract_switch" onchange="User.checkAddContract(this);" <?= $addContract ? 'checked' : ''; ?> autocomplete="off">
                Добавить договор
            </label>
        </div>
    </div>
</div>

<div id="add_payment" <?= $addPayment ? '' : 'class="hidden"'; ?>>
    <div class="form-group">
        <label for="amount">Сумма</label>
        <input id="amount" class="form-control" name="payment[amount]" type="number" step="1" min="1" required
            <?= array_key_exists('amount', $paymentData) ? 'value="' . $paymentData['amount'] . '"' : 'disabled'; ?>>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" class="input-discount" name="payment[discount]" value="1" autocomplete="off"
                <?= array_key_exists('discount', $paymentData) ? 'checked' : 'disabled'; ?>> Это платёж по скидочной цене
        </label>
    </div>
    <div class="form-group">
        <label for="comment">Комментарий к платежу</label>
        <input id="comment" class="form-control" name="payment[comment]"
            <?= array_key_exists('comment', $paymentData) ? 'value="' . $paymentData['comment'] . '"' : ''; ?>>
    </div>
    <div class="radio">
        <label>
            <input type="radio" name="payment[contractType]" id="payment_type_auto" value="auto" onchange="User.checkContractType(this);"
                <?= !array_key_exists('contractType', $paymentData) || $paymentData['contractType'] == 'auto' ? 'checked' : ''; ?>>
            Создать договор автоматически
        </label>
    </div>
    <div class="radio">
        <label class="form-inline">
            <input type="radio" name="payment[contractType]" id="payment_type_manual" value="manual" onchange="User.checkContractType(this);"
                <?= array_key_exists('contractType', $paymentData) && $paymentData['contractType'] == 'manual' ? 'checked' : ''; ?>>
            Старый договор, номер: <input id="contract" class="form-control" name="payment[contract]" required
                <?= array_key_exists('contractType', $paymentData) && $paymentData['contractType'] == 'manual' ? '' : 'disabled'; ?>
                <?= array_key_exists('contract', $paymentData) ? 'value="' . $paymentData['contract'] . '"' : ''; ?>>
        </label>
    </div>
</div>

<div id="add_contract" <?= $addContract ? '' : 'class="hidden"'; ?>>
    <div class="form-group">
        <label for="contract_amount">Сумма</label>
        <input id="contract_amount" class="form-control" name="contract[amount]" type="number" step="1" min="1" required <?= array_key_exists('amount', $contractData) ? 'value="' . $contractData['amount'] . '"' : 'disabled'; ?>>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="contract[discount]" value="1" autocomplete="off" onchange="User.checkDiscountContract(this);" <?= array_key_exists('discount', $contractData) ? 'checked' : ''; ?>> Цена со скидкой
        </label>
    </div>
    <div id="contract_group_block" class="form-group <?= $addGroup ? 'class="hidden"' : ''; ?>">
        <label for="contract_group">Группа</label>
        <select id="contract_group" class="form-control" name="contract[group]">
            <?= $getGroupOptionsList(array_key_exists('group', $contractData) ? intval($contractData['group']) : 0); ?>
        </select>
    </div>
</div>