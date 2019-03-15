<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \common\models\GiftCardSearch */

$this->title = 'Предоплаченные карты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="gift-card-type-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return  = [];
            switch ($model->status) {
                case \common\models\GiftCard::STATUS_PAID:
                    $return['class'] = 'success';
                    break;
                case \common\models\GiftCard::STATUS_USED:
                    $return['class'] = 'warning';
                    break;
            }
            return $return;
        },
        'columns' => [
            'name',
            'amount',
            'customer_name',
            [
                'attribute' => 'customer_phone',
                'content' => function ($model, $key, $index, $column) {
                    return "<nobr>{$model->phoneFull}</nobr>";
                },
            ],
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
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
            ],
            [
                'attribute' => 'paid_at',
                'format' => 'datetime',
                'filter' => \dosamigos\datepicker\DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'paidDateString',
                    'template' => '{addon}{input}',
                    'clientOptions' => [
                        'weekStart' => 1,
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                    ],
                ]),
            ],
            [
                'attribute' => 'used_at',
                'format' => 'datetime',
                'filter' => \dosamigos\datepicker\DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'usedDateString',
                    'template' => '{addon}{input}',
                    'clientOptions' => [
                        'weekStart' => 1,
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                    ],
                ]),
            ],
        ],
    ]); ?>
</div>
