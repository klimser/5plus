<?php

use yii\helpers\Html;
use yii\grid\GridView;
use dosamigos\datepicker\DatePicker;

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
                'options' => ['class' => 'col-xs-2'],
            ],
            [
                'label' => 'Результат',
                'content' => function ($model, $key, $index, $column) {
                    return $model->rightAnswerCount . ' из ' . count($model->answersArray);
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{delete}',
                'buttonOptions' => ['class' => 'btn btn-default'],
            ],
        ],
    ]); ?>
</div>
