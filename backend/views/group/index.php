<?php

use common\components\helpers\Calendar;
use common\models\Group;
use common\models\GroupSearch;
use common\models\Subject;
use common\models\Teacher;
use yii\grid\ActionColumn;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
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

$this->title = 'Группы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="group-index">

    <div class="float-right"><a href="<?= Url::to(['inactive']); ?>">Завершённые группы</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить группу', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model, $key, $index, $grid) {return ($model->active == Group::STATUS_INACTIVE) ? ['class' => 'inactive'] : [];},
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'name',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) use ($subjectMap) {
                    return Html::a($model->name, Url::to(['group/view', 'id' => $model->id]));
                },
            ],
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
                'attribute' => 'schedule',
                'format' => 'text',
                'header' => 'Расписание',
                'content' => function ($model, $key, $index, $column) {
                    /* @var $model Group */
                    $data = [];
                    for ($i = 0; $i < 7; $i++) {
                        if (!empty($model->scheduleData[$i])) {
                            $data[] = '<span class="text-nowrap">' . Calendar::$weekDaysShort[($i + 1) % 7] . ": {$model->scheduleData[$i]}</span>";
                        }
                    }
                    return implode('<br>', $data);
                },
            ],
            [
                'format' => 'text',
                'header' => 'Ученики',
                'content' => function ($model, $key, $index, $column) {
                    return '<div class="pupils"><span class="text-nowrap">' . count($model->activeGroupPupils) . ' учеников '
                        . '<a href="#" onclick="$(this).closest(\'.pupils\').find(\'.pupil_list\').toggle(); return false;"><span class="glyphicon glyphicon-chevron-down"></span></a></span>'
                        . '<div class="pupil_list" style="display: none;">'
                        . implode('<br>', ArrayHelper::getColumn($model->activeGroupPupils, 'user.name'))
                        . '</div></div>';
                },
            ],
            [
                'class' => ActionColumn::class,
                'template' => $canEdit ? '{update}' : '',
                'buttonOptions' => ['class' => 'btn btn-default'],

            ],
        ],
    ]); ?>
</div>
