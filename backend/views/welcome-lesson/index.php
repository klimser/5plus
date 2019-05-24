<?php

use backend\models\WelcomeLesson;
use common\models\Group;use common\models\PaymentSearch;
use common\models\Subject;
use common\models\Teacher;
use common\models\User;
use dosamigos\datepicker\DatePicker;
use yii\grid\Column;
use yii\bootstrap\Html;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel PaymentSearch */
/* @var $studentMap User[] */
/* @var $subjectMap Subject[] */
/* @var $teacherMap Teacher[] */
/* @var $groups Group[] */

$this->title = 'Пробные уроки';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="welcome-lesson-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div id="messages_place"></div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return = [];
            switch ($model->status) {
                case WelcomeLesson::STATUS_PASSED:
                    $return['class'] = 'success';
                    break;
                case WelcomeLesson::STATUS_MISSED:
                    $return['class'] = 'warning';
                    break;
                case WelcomeLesson::STATUS_CANCELED:
                    $return['class'] = 'info';
                    break;
                case WelcomeLesson::STATUS_DENIED:
                    $return['class'] = 'danger';
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
                'attribute' => 'subject_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->subject->name;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'subject_id',
                    $subjectMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'teacher_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->teacher->name;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'teacher_id',
                    $teacherMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'lesson_date',
                'format' => 'datetime',
                'label' => 'Дата',
                'filter' => DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'lessonDateString',
                    'template' => '{addon}{input}',
                    'clientOptions' => [
                        'weekStart' => 1,
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                    ],
                ]),
            ],
            [
                'attribute' => 'status',
                'content' => function ($model, $key, $index, $column) {
                    return WelcomeLesson::STATUS_LABELS[$model->status];
                },
            ],
            [
                'class' => Column::class,
                'content' => function ($model, $key, $index, $column) {
                    return '<script>$(function() { WelcomeLesson.setButtons($(\'tr[data-key="' . $model->id . '"] td:last-child\'), ' . $model->id . ', ' . $model->status . ') });</script>';
                },
            ],
        ],
    ]); ?>

    <div class="modal fade" id="moving-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="moving-form">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Modal title</h4>
                    </div>
                    <div class="modal-body">
                        <h3 id="pupil"></h3>
                        <div id="start_date"></div>
                        <b>Группа</b>
                        <div id="group_proposal"></div>
                        <input type="radio" name="group_proposal" value="0" onchange="WelcomeLesson.groupChange(this);"> Другая
                        <select name="group_id" id="other_group" disabled>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group->id; ?>"><?= $group->name; ?> (<?= $group->teacher->name; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">отмена</button>
                        <button class="btn btn-primary">В группу!</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
