<?php

use common\components\DefaultValuesComponent;
use \yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $teachers \common\models\Teacher[] */
/* @var $subjects \common\models\Subject[] */

$this->title = 'Акты оказания услуг';
$this->params['breadcrumbs'][] = $this->title;


?>

<?= Html::beginForm('', 'post'); ?>
    <div class="row align-items-center">
        <div class="col-8 col-lg-10">
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
        </div>
        <div class="col-4 col-lg-2">
            <?= Html::submitButton('Все учителя', ['name' => 'all', 'value' => 1, 'class' => 'btn btn-primary']); ?>
        </div>
    </div>

    <div class="row align-items-center">
        <div class="col-8 col-lg-10">
            <div class="form-group">
                <label for="report-teacher">Учитель</label>
                <?= Html::dropDownList(
                    'teacher_id',
                    null,
                    ArrayHelper::map($teachers, 'id', 'name'),
                    ['id' => 'report-teacher', 'class' => 'form-control']
                ); ?>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <?= Html::submitButton('Все предметы', ['name' => 'one-teacher', 'value' => 1, 'class' => 'btn btn-primary']); ?>
        </div>
    </div>

    <div class="row align-items-center">
        <div class="col-8 col-lg-10">
            <div class="form-group">
                <label for="report-subject">Предмет</label>
                <?= Html::dropDownList(
                    'subject_id',
                    null,
                    ArrayHelper::map($subjects, 'id', 'name'),
                    ['id' => 'report-subject', 'class' => 'form-control']
                ); ?>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <?= Html::submitButton('Один предмет', ['name' => 'one-subject', 'class' => 'btn btn-primary']); ?>
        </div>
    </div>
<?= Html::endForm(); ?>
