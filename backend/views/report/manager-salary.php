<?php

use common\components\DefaultValuesComponent;
use \yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */

$this->title = 'Зарплата менеджеров';
$this->params['breadcrumbs'][] = $this->title;


?>
<?= Html::beginForm(); ?>
    <div class="form-group">
        <label for="report-month">День</label>
        <?= DatePicker::widget(ArrayHelper::merge(
            DefaultValuesComponent::getDatePickerSettings(),
            [
                'id' => 'report-month',
                'name' => 'date',
                'value' => date('d.m.Y'),
            ])); ?>
    </div>
    <div class="form-group">
        <div class="form-check">
            <label>
                <input class="form-check-input" type="radio" name="month" value="0">
                за день
            </label>
        </div>
        <div class="form-check">
            <label>
                <input class="form-check-input" type="radio" name="month" value="1" checked>
                за месяц
            </label>
        </div>
    </div>
    <div class="form-group">
        <button class="btn btn-primary">Получить</button>
    </div>
<?= Html::endForm(); ?>
