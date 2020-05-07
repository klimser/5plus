<?php

use yii\bootstrap4\Html;
use dosamigos\datepicker\DatePicker;
use common\components\helpers\Calendar;

/* @var $this yii\web\View */
/* @var $groupMap array */

$this->title = 'Заполнить календарь занятий';
$this->params['breadcrumbs'][] = ['label' => 'Группы', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Календарь занятий';

$getHoursOptions = function() {
    $html = '';
    for ($i = 7; $i < 22; $i++) $html .= "<option value=\"$i\">$i</option>";
    return $html;
};
$getMinutesOptions = function() {
    $html = '';
    for ($i = 0; $i < 60; $i += 5) $html .= "<option value=\"$i\">" . ($i < 10 ? '0' : '') . "$i</option>";
    return $html;
};

$this->registerJs(<<<SCRIPT
function toggleWeekday(e) {
    var index = $(e).data("day");
    if ($(e).is(":checked")) {
        $("#time_edit_" + index).removeAttr("disabled").addClass("bg-success");
    } else {
        $("#time_edit_" + index).attr("disabled", true).removeClass("bg-success");
    }
}
SCRIPT
, \frontend\components\extended\View::POS_END);
?>
<h1><?= Html::encode($this->title) ?></h1>

<?= Html::beginForm(); ?>
<div class="form-group">
    <label>Группа</label>
    <?= Html::dropDownList('group', null, $groupMap, ['class' => 'form-control']); ?>
</div>

<div class="form-group">
    <label>Период занятий</label>
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <?=  DatePicker::widget([
                'name' => 'date_from',
                'template' => '<span class="input-group-addon">С</span>{input}',
                'clientOptions' => [
                    'weekStart' => 1,
                    'autoclose' => true,
                    'format' => 'yyyy-mm-dd'
                ],
                'options' => ['required' => true],
            ]); ?>
        </div>
        <div class="col-xs-12 col-sm-6">
            <?=  DatePicker::widget([
                'name' => 'date_to',
                'template' => '<span class="input-group-addon">ПО</span>{input}',
                'clientOptions' => [
                    'weekStart' => 1,
                    'autoclose' => true,
                    'format' => 'yyyy-mm-dd'
                ],
                'options' => ['required' => true],
            ]); ?>
        </div>
    </div>
</div>

<div class="form-group">
    <div class="row">
        <?php for ($i = 1; $i <= 7; $i++): ?>
            <div class="col-xs-2">
                <label>
                    <?= Html::checkbox("weekday[$i]", false, ['data' => ['day' => $i], 'onclick' => 'toggleWeekday(this);']); ?>
                    <?= Calendar::$weekDays[$i % 7]; ?>
                </label>
                <br>
                <fieldset id="time_edit_<?= $i; ?>" disabled>
                    <div class="input-group">
                        <select class="form-control time_edit_control input-sm" name="hours_<?= $i; ?>"><?= $getHoursOptions(); ?></select>
                        <select class="form-control time_edit_control input-sm" name="minutes_<?= $i; ?>"><?= $getMinutesOptions(); ?></select>
                    </div>
                </fieldset>
            </div>
        <?php endfor; ?>
    </div>
</div>

<div class="form-group">
    <?= Html::submitButton('Заполнить', ['class' => 'btn btn-primary']); ?>
</div>

<?= Html::endForm(); ?>
