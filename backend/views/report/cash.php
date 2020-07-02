<?php

use common\components\DefaultValuesComponent;
use \yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $groups \common\models\Group[] */
/* @var $allowedTotal bool */

$this->title = 'Кассовый отчёт';
$this->params['breadcrumbs'][] = $this->title;


?>
<?= Html::beginForm('', 'post'); ?>
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
            <input class="form-check-input" type="radio" name="kids" id="non-kids" value="0" checked>
            <label class="form-check-label" for="non-kids">
                не KIDS
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="kids" id="kids" value="1" checked>
            <label class="form-check-label" for="kids">
                KIDS
            </label>
        </div>
    </div>
    <div class="form-group">
        <button class="btn btn-primary">Получить</button>
    </div>
<?= Html::endForm(); ?>
