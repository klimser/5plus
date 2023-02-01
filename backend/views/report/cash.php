<?php

use common\components\DefaultValuesComponent;
use \yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $courseCategories \common\models\CourseCategory[] */

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
        <label for="report-course-type">Категория</label>
        <?= Html::dropDownList(
                'category',
                null,
                ArrayHelper::map($courseCategories, 'id', 'name'),
                ['id' => 'report-course-type', 'class' => 'form-control'],
        ); ?>
    </div>
    <div class="form-group">
        <button class="btn btn-primary">Получить</button>
    </div>
<?= Html::endForm(); ?>
