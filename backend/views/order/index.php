<?php

use common\components\DefaultValuesComponent;
use common\models\Order;
use common\models\OrderSearch;
use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\bootstrap4\Html;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel OrderSearch */

$this->title = 'Заявки';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive-xl'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            switch ($model->status) {
                case 'unread':
                    $class = 'table-info';
                    break;
                case 'done':
                    $class = 'table-success';
                    break;
                case 'problem':
                    $class = 'table-danger';
                    break;
            }
            $return = ['title' => $model->admin_comment];
            if (isset($class)) {
                $return['class'] = $class;
            }
            return $return;
        },
        'columns' => [
            'subject',
            [
                'attribute' => 'name',
                'header' => 'Имя',
                'content' => function ($model, $key, $index, $column) {
                    $name = $model->name;
                    if ($model->tg_login) {
                        return Html::a($name, 'https://t.me/' . $model->tg_login, ['target' => '_blank']);
                    }
                    return $name;
                }
            ],
            [
                'attribute' => 'phone',
                'header' => 'Телефон',
//                'content' => function ($model, $key, $index, $column) { return "<span class='text-nowrap'>{$model->phoneFull}</span>"; },
            ],
            [
                'attribute' => 'user_comment',
                'header' => 'Комментарии',
                'content' => function ($model, $key, $index, $column) {
                    $content = $model->user_comment;
                    if ($model->admin_comment) {
                        $content .= '<br><i>Комментарий админа:</i> ' . $model->admin_comment;
                    }
                    return $content ?: '<span class="not-set">(не задано)</span>';
                },
            ],
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
            'source',
            [
                'attribute' => 'status',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::activeDropDownList($model, 'status', Order::$statusLabels, ['class' => 'form-control input-sm', 'onchange' => 'Main.changeEntityStatus("order", ' . $model->id . ', $(this).val(), this);', 'id' => 'order-status-' . $key, 'autocomplete' => 'off']);
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'status',
                    array_merge(['' => 'Любой'], Order::$statusLabels),
                    ['class' => 'form-control']
                ),
            ],
            [
                'class' => ActionColumn::class,
                'template' => '<span class="text-nowrap">{update}{delete}</span>',
                'buttons' => [
                    'update' =>  function($url,$model) {
                        return Html::a('<span class="fas fa-pencil-alt"></span>', $url, [
                            'title' => Yii::t('yii', 'Update'),
                            'class' => 'btn btn-outline-dark',
                        ]);
                    },
                    'delete' =>  function($url,$model) {
                        return Html::a('<span class="fas fa-trash-alt"></span>', $url, [
                            'title' => Yii::t('yii', 'Delete'),
                            'class' => 'btn btn-outline-dark ml-2',
                            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

</div>
