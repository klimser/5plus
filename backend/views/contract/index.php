<?php

use common\components\DefaultValuesComponent;
use yii\helpers\Html;
use yii\grid\GridView;
use \common\models\Contract;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \common\models\ContractSearch */
/* @var $studentMap \common\models\User[] */
/* @var $groupMap \common\models\User[] */

$this->title = 'Договоры';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="payment-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return = [];
            switch ($model->status) {
                case Contract::STATUS_PAID:
                    if ($model->discount) $return['class'] = 'info';
                    else $return['class'] = 'success';
                    break;
                case Contract::STATUS_PROCESS:
                    $return['class'] = 'warning';
                    break;
            }
            return $return;
        },
        'columns' => [
            'number',
            [
                'attribute' => 'user_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->user->name;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'user_id',
                    $studentMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'group_id',
                'label' => 'Группа',
                'content' => function ($model, $key, $index, $column) {
                    return $model->group_id ? Html::a($model->group->name, \yii\helpers\Url::to(['group/view', 'id' => $model->group_id])) : null;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'group_id',
                    $groupMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'amount',
                'filter' => \kartik\field\FieldRange::widget([
                    'model' => $searchModel,
                    'attribute1' => 'amountFrom',
                    'attribute2' => 'amountTo',
//                    'name1'=>'amountFrom',
//                    'name2'=>'amountTo',
                    'separator' => '-',
                    'template' => '{widget}',
                    'type' => \kartik\field\FieldRange::INPUT_TEXT,
                ]),
                'contentOptions' => ['class' => 'text-right'],
            ],
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
                'filter' => DatePicker::widget(array_merge_recursive(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'model' => $searchModel,
                        'attribute' => 'createDateString',
                        'dateFormat' => 'y-M-dd',
                        'options' => [
                            'pattern' => '\d{4}-\d{2}-\d{2}',
                        ],
                    ])),
            ],
            [
                'attribute' => 'paid_at',
                'format' => 'datetime',
                'filter' => DatePicker::widget(array_merge_recursive(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'model' => $searchModel,
                        'attribute' => 'paidDateString',
                        'dateFormat' => 'y-M-dd',
                        'options' => [
                            'pattern' => '\d{4}-\d{2}-\d{2}',
                        ],
                    ])),
            ],
            [
                'class' => \yii\grid\ActionColumn::class,
                'template' => '{print}',
                'buttons' => [
                    'print' => function ($url, $model, $key) {
                        return Html::a(Html::tag('span', '', ['class' => 'fas fa-print']), \yii\helpers\Url::to(['contract/print', 'id' => $model->id]), ['class' => 'btn btn-default', 'title' => 'Печать']);
                    },
                ],
            ]
        ],
    ]); ?>
</div>
