<?php

/* @var $this yii\web\View */
/* @var $type string */
/* @var $from null|DateTimeImmutable */
/* @var $to null|DateTimeImmutable */
/* @var $totalAmount int */
/* @var $contracts \common\models\Contract[] */

use common\components\DefaultValuesComponent;
use common\components\helpers\Html;
use common\components\helpers\MoneyHelper;
use common\models\Contract;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

$this->title = 'Денежные поступления';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>

<div class="card p-2">
<div class="form-inline">
<?= Html::beginForm('', 'get'); ?>

<select name="type" class="form-control">
    <?php foreach (Contract::PAYMENT_TYPE_LABELS as $key => $label): ?>
        <option value="<?= $key; ?>" <?= isset($type) && $key === $type ? ' selected ' : ''; ?>><?= $label; ?></option>
    <?php endforeach; ?>
</select>
<?= DatePicker::widget(
    ArrayHelper::merge(
        DefaultValuesComponent::getDatePickerSettings(),
        [
            'dateFormat' => 'y-MM-dd',
            'name' => 'from',
            'value' => isset($from) ? $from->format('Y-m-d') : '',
            'options' => [
                'pattern' => '\d{4}-\d{2}-\d{2}',
                'required' => true,
                'autocomplete' => 'off',
                'id' => 'dateFrom',
                'onchange' => 'Main.handleDateRangeFrom(this)',
                'data' => ['target-to-selector' => '#dateTo'],
            ],
        ]
    )
    );
?>
-
<?= DatePicker::widget(
    ArrayHelper::merge(
        DefaultValuesComponent::getDatePickerSettings(),
        [
            'dateFormat' => 'y-MM-dd',
            'name' => 'to',
            'value' => isset($to) ? $to->format('Y-m-d') : '',
            'options' => [
                'pattern' => '\d{4}-\d{2}-\d{2}',
                'required' => true,
                'autocomplete' => 'off',
                'id' => 'dateTo',
                'onchange' => 'Main.handleDateRangeTo(this)',
                'data' => ['target-to-selector' => '#dateFrom'],
            ],
        ]
    )
);
?>

<?= Html::submitButton('получить', ['class' => 'btn btn-primary']); ?>

<?= Html::endForm(); ?>
</div>
</div>

<?php if (isset($totalAmount) && $totalAmount > 0): ?>
    <hr>
    <p><b>Итого:</b> <?= MoneyHelper::formatThousands($totalAmount); ?></p>
    <table class="table table-bordered table-striped">
        <?php foreach ($contracts as $contract): ?>
            <tr>
                <td><?= $contract->paidDate->format('d.m.Y H:i:s'); ?></td>
                <td><?= $contract->user->name; ?></td>
                <td><?= $contract->course->name; ?></td>
                <td class="text-right"><?= MoneyHelper::formatThousands($contract->amount); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
