<?php

use common\components\DefaultValuesComponent;
use \yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $courses \common\models\Course[] */
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
        <label for="report-group">Группа</label>
        <?= Html::dropDownList(
            'group',
            null,
            array_merge(
                    $allowedTotal ? ['all' => 'Все группы'] : [],
                ArrayHelper::map($courses, function ($elem) {return (string) $elem->id;}, 'name')
            ),
            ['id' => 'report-group', 'class' => 'form-control']
        ); ?>
    </div>
    <button class="btn btn-primary">Получить</button>
<?= Html::endForm(); ?>
