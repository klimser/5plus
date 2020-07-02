<?php

use common\components\DefaultValuesComponent;
use common\models\Feedback;
use yii\bootstrap4\Html;
use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

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
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            switch ($model->status) {
                case 'new':
                    $class = 'table-info';
                    break;
                case 'completed':
                    $class = 'table-success';
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
                'filter' => DatePicker::widget(ArrayHelper::merge(
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
                'attribute' => 'status',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::activeDropDownList($model, 'status', Feedback::STATUS_LABELS, ['class' => 'form-control input-sm', 'onchange' => 'Main.changeEntityStatus("feedback", ' . $model->id . ', $(this).val(), this);', 'id' => 'feedback-status-' . $key]);
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'status',
                    array_merge(['' => 'Любой'], Feedback::STATUS_LABELS),
                    ['class' => 'form-control']
                ),
                'headerOptions' => ['style' => 'min-width: 120px;'],
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{delete}',
                'buttons' => [
                    'delete' =>  function($url,$model) {
                        return Html::a('<span class="fas fa-trash-alt"></span>', $url, [
                            'title' => Yii::t('yii', 'Delete'),
                            'class' => 'btn btn-outline-dark',
                            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
