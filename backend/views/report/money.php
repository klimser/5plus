<?php

use common\components\DefaultValuesComponent;
use \yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $courseMap \common\models\Course[] */
/* @var $allowedTotal bool */

$this->title = 'Финансовый отчёт';
$this->params['breadcrumbs'][] = $this->title;

?>

<?= Html::beginForm(); ?>
    <div class="form-group">
        <label for="report-month">Месяц</label>
        <?= DatePicker::widget(ArrayHelper::merge(
            DefaultValuesComponent::getDatePickerSettings(),
            [
                'id' => 'report-month',
                'name' => 'date',
                'value' => date('d.m.Y'),
                'dateFormat' => 'MM.y',
                'options' => [
                    'pattern' => '\d{2}.\d{4}',
                ],
            ])); ?>
    </div>
    <div class="form-group">
        <label for="report-course">Группа</label>
        <?= Html::dropDownList(
            'course',
            null,
            $courseMap,
            ['id' => 'report-course', 'class' => 'form-control']
        ); ?>
    </div>
    <button class="btn btn-primary">Получить</button>
<?= Html::endForm(); ?>
