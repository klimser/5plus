<?php

use common\models\Group;
use common\models\GroupSearch;
use common\models\Subject;
use common\models\Teacher;
use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\bootstrap4\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $searchModel GroupSearch */
/* @var $dataProvider ActiveDataProvider */
/* @var $subjectMap Subject[] */
/* @var $teacherMap Teacher[] */
/* @var $canEdit bool */
/* @var $isTeacher bool */

$this->title = 'Темы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="group-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $key, $index, $grid) {return ($model->active == Group::STATUS_INACTIVE) ? ['class' => 'table-secondary'] : [];},
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            [
                'attribute' => 'subject_id',
                'format' => 'text',
                'content' => function ($model, $key, $index, $column) use ($subjectMap) {
                    return $subjectMap[$model->subject_id];
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'subject_id',
                    $subjectMap,
                    ['prompt' => 'Все', 'class' => 'form-control']
                )
            ],
            [
                'attribute' => 'teacher_id',
                'format' => 'text',
                'content' => function ($model, $key, $index, $column) use ($teacherMap) {
                    return $model->teacher_id ? $model->teacher->name : '<span class="not-set">(не задано)</span>';
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'teacher_id',
                    $teacherMap,
                    ['prompt' => 'Все', 'class' => 'form-control']
                )
            ],
            [
                'format' => 'text',
                'header' => 'Тема',
                'content' => (fn($model, $key, $index, $column) => ($model->note ? $model->note->topic : '')),
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{history}',
                'buttons' => [
                    'history' =>  function($url,$model) {
                        return Html::a('<span class="fas fa-history"></span>', Url::to(['group/note', 'id' => $model->id]), [
                            'title' => 'История изменений',
                            'class' => 'btn btn-outline-dark',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
