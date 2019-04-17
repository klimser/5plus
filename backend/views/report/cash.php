<?php

use \yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $groups \common\models\Group[] */
/* @var $allowedTotal bool */

$this->title = 'Кассовый отчёт';
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="row">
    <?= Html::beginForm('', 'post'); ?>
        <div class="form-group">
            <label for="report-month">День</label>
            <?= \dosamigos\datepicker\DatePicker::widget([
                'id' => 'report-month',
                'name' => 'date',
                'value' => date('d.m.Y'),
                'clientOptions' => [
                    'autoclose' => true,
                    'format' => 'dd.mm.yyyy',
                    'language' => 'ru',
                ]
            ]);?>
        </div>
        <div class="radio">
            <label>
                <input type="radio" name="kids" value="0" checked> не KIDS
            </label>
        </div>
        <div class="radio">
            <label>
                <input type="radio" name="kids" value="1"> KIDS
            </label>
        </div>
        <button class="btn btn-primary">Получить</button>
    <?= Html::endForm(); ?>
</div>