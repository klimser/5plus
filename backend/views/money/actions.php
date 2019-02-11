<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \backend\models\ActionSearch */
/* @var $studentMap string[] */
/* @var $adminMap string[] */
/* @var $groupMap string[] */
/* @var $typeMap string[] */

$this->title = 'Действия';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="actions-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return = [];
            if ($model->amount > 0) $return['class'] = 'success';
            elseif ($model->amount < 0) $return['class'] = 'danger';
            return $return;
        },
        'columns' => [
            [
                'attribute' => 'admin_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->admin->name;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'admin_id',
                    $adminMap,
                    ['class' => 'form-control']
                ),
                'options' => ['class' => 'col-xs-2'],
            ],
            [
                'attribute' => 'type',
                'content' => function ($model, $key, $index, $column) {
                    return '<nobr>' . \common\components\Action::$typeLabels[$model->type] . '</nobr>';
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'type',
                    $typeMap,
                    ['class' => 'form-control']
                ),
//                'options' => ['class' => 'col-xs-2'],
            ],
            [
                'attribute' => 'user_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->user_id ? $model->user->name : null;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'user_id',
                    $studentMap,
                    ['class' => 'form-control']
                ),
                'options' => ['class' => 'col-xs-2'],
            ],
            [
                'attribute' => 'group_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->group_id ? $model->group->name : null;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'group_id',
                    $groupMap,
                    ['class' => 'form-control']
                ),
                'options' => ['class' => 'col-xs-2'],

            ],
            [
                'attribute' => 'amount',
                'filter' => \kartik\field\FieldRange::widget([
                    'model' => $searchModel,
                    'name1'=>'amountFrom',
                    'name2'=>'amountTo',
                    'separator' => '-',
                    'template' => '{widget}',
                    'type' => \kartik\field\FieldRange::INPUT_TEXT,
                ]),
                'contentOptions' => ['class' => 'text-right'],
//                'options' => ['class' => 'col-xs-1'],
            ],
            [
                'attribute' => 'comment',
//                'options' => ['class' => 'col-xs-1'],
            ],
            [
                'attribute' => 'createDate',
                'format' => 'datetime',
                'label' => 'Дата операции',
                'filter' => \dosamigos\datepicker\DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'createDateString',
                    'template' => '{addon}{input}',
                    'clientOptions' => [
                        'weekStart' => 1,
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                    ],
                ]),
                'options' => ['class' => 'col-xs-2'],
            ],
        ],
    ]); ?>
</div>