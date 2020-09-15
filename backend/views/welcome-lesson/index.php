<?php

use backend\models\WelcomeLesson;
use backend\models\WelcomeLessonSearch;
use common\components\DefaultValuesComponent;
use common\models\Group;
use common\models\Subject;
use common\models\Teacher;
use common\models\User;
use yii\bootstrap4\LinkPager;
use yii\grid\Column;
use yii\bootstrap4\Html;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel WelcomeLessonSearch */
/* @var $studentMap User[] */
/* @var $subjectMap Subject[] */
/* @var $teacherMap Teacher[] */
/* @var $groupMap Group[] */
/* @var $statusMap string[] */
/* @var $reasonsMap string[] */
/* @var $groups Group[] */

$this->title = 'Пробные уроки';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs(<<<SCRIPT
WelcomeLesson.init();
SCRIPT
);

?>
<div class="welcome-lesson-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div id="messages_place"></div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            /** @var WelcomeLesson $model */
            $return = ['class' => 'welcome-row', 'data' => [
                'date' => $model->lessonDateTime->format('d.m.Y'),
                'status' => $model->status,
                'deny-reason' => $model->deny_reason]];
            switch ($model->status) {
                case WelcomeLesson::STATUS_PASSED:
                    $return['class'] .= ' table-success';
                    break;
                case WelcomeLesson::STATUS_MISSED:
                    $return['class'] .= ' table-warning';
                    break;
                case WelcomeLesson::STATUS_CANCELED:
                    $return['class'] .= ' table-info';
                    break;
                case WelcomeLesson::STATUS_DENIED:
                    $return['class'] .= ' table-danger';
                    break;
            }
            return $return;
        },
        'columns' => [
            [
                'attribute' => 'user_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->user->name;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'user_id',
                    $studentMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'group_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->group_id ? $model->group->name : '-';
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'group_id',
                    $groupMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'subjectId',
                'content' => function ($model, $key, $index, $column) {
                    return $model->group_id ? $model->group->subject->name : '';
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'subjectId',
                    $subjectMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'teacherId',
                'content' => function ($model, $key, $index, $column) {
                    return $model->group_id ? $model->group->teacher->name : '';
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'teacherId',
                    $teacherMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'lesson_date',
                'format' => 'datetime',
                'label' => 'Дата',
                'filter' => DatePicker::widget(
                    ArrayHelper::merge(DefaultValuesComponent::getDatePickerSettings(),
                        [
                    'model' => $searchModel,
                    'attribute' => 'lessonDateString',
                    'dateFormat' => 'y-MM-dd',
                    'options' => [
                        'pattern' => '\d{4}-\d{2}-\d{2}',
                        'autocomplete' => 'off',
                    ],
                ])),
            ],
            [
                'attribute' => 'status',
                'content' => function ($model, $key, $index, $column) {
                    return WelcomeLesson::STATUS_LABELS[$model->status];
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'status',
                    $statusMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'deny_reason',
                'content' => function ($model, $key, $index, $column) {
                    if (!$model->deny_reason) return '';
                    $content = WelcomeLesson::DENY_REASON_LABELS[$model->deny_reason];
                    if ($model->comment) {
                        $content .= '<br><div class="label label-info"><small>' . nl2br($model->comment) . '</small></div>';
                    }
                    return $content;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'deny_reason',
                    $reasonsMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'class' => Column::class,
                'header' => 'Действия',
                'contentOptions' => ['class' => 'buttons-column'],
                'content' => function ($model, $key, $index, $column) {
                    return '';
                }
            ],
        ],
    ]); ?>

    <?= $this->render('_modal'); ?>
</div>
