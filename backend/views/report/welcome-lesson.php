<?php

use common\components\DefaultValuesComponent;
use \yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */

$this->title = 'Отчёт о пробных уроках';
$this->params['breadcrumbs'][] = $this->title;


?>

<?= Html::beginForm('', 'post', ['class' => 'form-inline']); ?>
    <div class="form-group">
        <div class="input-group">
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
            <div class="input-group-append">
                <button class="btn btn-primary">Получить</button>
            </div>
        </div>
    </div>
<?= Html::endForm(); ?>
