<?php

use \yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $groups \common\models\Group[] */
/* @var $allowedTotal bool */

$this->title = 'Финансовый отчёт';
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="row">
    <?= Html::beginForm('', 'post'); ?>
        <div class="form-group">
            <label for="report-month">Месяц</label>
            <?= \dosamigos\datepicker\DatePicker::widget([
                'id' => 'report-month',
                'name' => 'date',
                'value' => date('m.Y'),
                'clientOptions' => [
                    'autoclose' => true,
                    'format' => 'mm.yyyy',
                    'language' => 'ru',
                    'viewMode' => 'months',
                ]
            ]);?>
        </div>
        <div class="form-group">
            <label for="report-group">Группа</label>
            <?= Html::dropDownList(
                'group',
                null,
                array_merge(
                        $allowedTotal ? ['all' => 'Все группы'] : [],
                    \yii\helpers\ArrayHelper::map($groups, function ($arr) {return 'group_' . strval($arr->id);}, 'name')
                ),
                ['id' => 'report-group', 'class' => 'form-control']
            ); ?>
        </div>
        <button class="btn btn-primary">Получить</button>
    <?= Html::endForm(); ?>
</div>