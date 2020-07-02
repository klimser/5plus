<?php

use common\components\DefaultValuesComponent;
use yii\bootstrap4\Html;
use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $searchModel common\models\QuizResultSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Результаты тестирования';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="quiz-result-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'columns' => [
            //'hash',
            [
                'attribute' => 'student_name',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::a($model->student_name, ['view', 'id' => $model->id]);
                },
            ],
            'subject_name',
            'quiz_name',
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
                'label' => 'Результат',
                'content' => function ($model, $key, $index, $column) {
                    return $model->rightAnswerCount . ' из ' . count($model->answersArray);
                },
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{delete}',
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
    ]); ?>
</div>
