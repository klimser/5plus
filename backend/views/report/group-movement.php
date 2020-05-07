<?php

use \yii\bootstrap4\Html;

/* @var $this yii\web\View */

$this->title = 'Отчет о движении студентов';
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="row">
    <div class="col-xs-12">
        <?= Html::beginForm('', 'post', ['class' => 'form-inline']); ?>
            <?= \dosamigos\datepicker\DatePicker::widget([
                'name' => 'date',
                'value' => date('m.Y'),
                'clientOptions' => [
                    'autoclose' => true,
                    'format' => 'mm.yyyy',
                    'language' => 'ru',
                    'viewMode' => 'months',
                ]
            ]);?>
            <button class="btn btn-primary">Получить</button>
        <?= Html::endForm(); ?>
    </div>
</div>
