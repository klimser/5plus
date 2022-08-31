<?php

use common\components\helpers\Calendar;
use common\components\helpers\WordForm;
use common\models\Course;
use common\models\CourseSearch;
use common\models\Subject;
use common\models\Teacher;
use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\helpers\ArrayHelper;
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
?>
<p>
    <?= Html::a('Добавить группу', ['create'], ['class' => 'btn btn-success']) ?>
</p>
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
            'content' => function (Course $model, $key, $index, $column) use ($subjectMap) {
                return Html::a($model->courseConfig->name, Url::to(['course/view', 'id' => $model->id]))
                    . ($model->note ? '<br><i>' . $model->note->topic . '</i>' : '');
            },
            'filter' => Html::activeTextInput($searchModel, 'name')
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
            'content' => static fn (Course $model, $key, $index, $column) => $model->courseConfig->teacher->name,
            'filter' => Html::activeDropDownList(
                $searchModel,
                'teacher_id',
                $teacherMap,
                ['prompt' => 'Все', 'class' => 'form-control']
            )
        ],
        [
            'attribute' => 'schedule',
            'format' => 'text',
            'header' => 'Расписание',
            'content' => function (Course $model, $key, $index, $column) {
                $data = [];
                for ($i = 0; $i < 7; $i++) {
                    if (!empty($model->courseConfig->schedule[$i])) {
                        $data[] = '<span class="text-nowrap">' . Calendar::$weekDaysShort[($i + 1) % 7] . ": {$model->courseConfig->schedule[$i]}</span>";
                    }
                }
                return implode('<br>', $data);
            },
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
            'class' => ActionColumn::class,
            'template' => $canEdit ? '{update}' : '',
            'buttons' => [
                'update' =>  function($url,$model) {
                    return Html::a('<span class="fas fa-pencil-alt"></span>', $url, [
                        'title' => Yii::t('yii', 'Update'),
                        'class' => 'btn btn-outline-dark',
                    ]);
                },
            ],
        ],
    ],
]); ?>
