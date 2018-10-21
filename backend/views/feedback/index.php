<?php

use yii\helpers\Html;
use yii\grid\GridView;
use dosamigos\datepicker\DatePicker;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \common\models\FeedbackSearch */

$this->title = 'Обратная связь';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="feedback-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model, $index, $widget, $grid) {
            switch ($model->status) {
                case 'new':
                    $class = 'info';
                    break;
                case 'completed':
                    $class = 'success';
                    break;
            }
            $return = [];
            if (isset($class)) {
                $return['class'] = $class;
            }
            return $return;
        },
        'columns' => [
            'name',
            'contact',
            'message',
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
                'filter' => DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'createDateString',
                    'template' => '{addon}{input}',
                    'clientOptions' => [
                        'weekStart' => 1,
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                    ],
                ]),
            ],
            [
                'attribute' => 'status',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::activeDropDownList($model, 'status', \common\models\Feedback::$statusLabels, ['class' => 'form-control input-sm', 'onchange' => 'Main.changeEntityStatus("feedback", ' . $model->id . ', $(this).val(), this);', 'id' => 'feedback-status-' . $key]);
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'status',
                    array_merge(['' => 'Любой'], \common\models\Feedback::$statusLabels),
                    ['class' => 'form-control']
                ),
            ],
            [
                'class' => \yii\grid\ActionColumn::class,
                'template' => '{delete}',
                'buttonOptions' => ['class' => 'btn btn-default'],
            ],
        ],
    ]); ?>
</div>
