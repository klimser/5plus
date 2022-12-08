<?php

use common\components\helpers\WordForm;
use common\models\Course;
use common\models\CourseSearch;
use common\models\Subject;
use common\models\Teacher;
use yii\bootstrap4\Html;
use yii\bootstrap4\LinkPager;
use yii\data\ActiveDataProvider;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\web\View;

/* @var $this View */
/* @var $searchModel CourseSearch */
/* @var $dataProvider ActiveDataProvider */
/* @var $subjectMap Subject[] */
/* @var $teacherMap Teacher[] */
/* @var $canEdit bool */;
?>
<div id="messages_place"></div>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'options' => ['class' => 'grid-view table-responsive'],
    'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
    'rowOptions' => static fn (Course $model, $key, $index, $grid) => ($model->active == Course::STATUS_INACTIVE) ? ['class' => 'table-secondary'] : [],
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        [
            'format' => 'html',
            'header' => 'Название',
            'content' => function (Course $model, $key, $index, $column) use ($subjectMap) {
                return $model->courseConfig->name . ($model->note ? '<br><i>' . $model->note->topic . '</i>' : '');
            },
            'filter' => Html::activeTextInput($searchModel, 'name', ['class' => 'form-control'])
        ],
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
            'format' => 'text',
            'header' => 'Студенты',
            'content' => function (Course $model, $key, $index, $column) {
                return '<div class="students"><span class="text-nowrap">' . count($model->activeCourseStudents) . ' ' . WordForm::getStudentsForm(count($model->activeCourseStudents)) . ' '
                    . '<button type="button" class="btn btn-link" onclick="$(this).closest(\'.students\').find(\'.student_list\').collapse(\'toggle\');">'
                    . '<span class="fas fa-chevron-down"></span>'
                    . '</button></span>'
                    . '<ul class="list-group student_list collapse"><li class="list-group-item p-1">'
                    . implode('</li><li class="list-group-item p-1">', ArrayHelper::getColumn($model->activeCourseStudents, 'user.name'))
                    . '</li></ul></div>';
            },
        ],
        [
            'format' => 'text',
            'header' => 'Тема',
            'content' => static fn(Course $model, $key, $index, $column) => ($model->note ? $model->note->topic : ''),
        ],
        [
            'class' => ActionColumn::class,
            'template' => '{update}',
            'buttons' => [
                'update' =>  function($url,$model) {
                    return Html::a('<span class="fas fa-pencil-alt"></span>', $url, [
                        'title' => Yii::t('yii', 'Update'),
                        'class' => 'btn btn-outline-dark collapse show',
                        'onclick' => 'Course.toggleNoteUpdate(this); return false;',
                        'data-group-id' => $model->id,
                    ]);
                },
            ],
        ],
    ],
]); ?>
