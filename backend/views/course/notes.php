<?php

use common\models\Course;
use common\models\CourseSearch;
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
/* @var $searchModel CourseSearch */
/* @var $dataProvider ActiveDataProvider */
/* @var $subjectMap Subject[] */
/* @var $teacherMap Teacher[] */
/* @var $canEdit bool */
/* @var $isTeacher bool */

$this->title = 'Темы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="course-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => static fn (Course $model, $key, $index, $grid) => ($model->active == Course::STATUS_INACTIVE) ? ['class' => 'table-secondary'] : [],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            [
                'attribute' => 'subject_id',
                'format' => 'text',
                'content' => static fn (Course $model, $key, $index, $column) => $subjectMap[$model->subject_id],
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
                'content' => static fn (Course $model, $key, $index, $column) => $model->courseConfig->teacher->name,
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
                'content' => static fn (Course $model, $key, $index, $column) => ($model->note ? $model->note->topic : ''),
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{history}',
                'buttons' => [
                    'history' =>  function($url,$model) {
                        return Html::a('<span class="fas fa-history"></span>', Url::to(['course/note', 'id' => $model->id]), [
                            'title' => 'История изменений',
                            'class' => 'btn btn-outline-dark',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
