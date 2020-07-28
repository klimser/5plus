<?php

use common\components\DefaultValuesComponent;
use common\models\OrderSearch;
use common\models\Review;
use common\models\Subject;
use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\bootstrap4\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel OrderSearch */

$this->title = 'Отзывы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="review-index">
    <div class="float-right"><a href="<?= Url::to(['page']); ?>">Настройки страницы отзывов</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $class = 'review-' . $model->id;
            if ($model->status == 'new') {
                $class .= ' table-warning';
            }

            return ['class' => $class];
        },
        'columns' => [
            [
                'attribute' => 'name',
            ],
            [
                'attribute' => 'message',
                'filter' => false,
            ],
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
                'filter' => \yii\jui\DatePicker::widget(ArrayHelper::merge(
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
                    return Review::$statusLabels[$model->status];
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'status',
                    array_merge(['' => 'Любой'], Review::$statusLabels),
                    ['class' => 'form-control']
                ),
            ],
            [
                'class' => ActionColumn::class,
                'template' => '<span class="text-nowrap">{approve}{update}{delete}</span>',
                'buttons' => [
                    'approve' => function ($url, $model, $key) {
                        if ($model->status == Review::STATUS_APPROVED) return false;
                        /** @var Subject $model */
                        return Html::a(Html::tag('span', '', ['class' => 'fas fa-check']), '#', ['class' => 'btn btn-outline-dark approve', 'title' => 'Утвердить', 'onclick' => 'return Main.changeEntityStatus("review", ' . $model->id . ', "' . Review::STATUS_APPROVED . '");']);
                    },
                    'update' =>  function($url,$model) {
                        return Html::a('<span class="fas fa-pencil-alt"></span>', $url, [
                            'title' => Yii::t('yii', 'Update'),
                            'class' => 'btn btn-outline-dark ml-2',
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
