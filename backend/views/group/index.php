<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\GroupSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $subjectMap \common\models\Subject[] */
/* @var $teacherMap \common\models\Teacher[] */
/* @var $canEdit bool */

$this->title = 'Группы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="group-index">

    <div class="pull-right"><a href="<?= \yii\helpers\Url::to(['inactive']); ?>">Завершённые группы</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить группу', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model, $key, $index, $grid) {return ($model->active == \common\models\Group::STATUS_INACTIVE) ? ['class' => 'inactive'] : [];},
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'name',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) use ($subjectMap) {
                    return Html::a($model->name, \yii\helpers\Url::to(['group/view', 'id' => $model->id]));
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
                    return array_key_exists($model->teacher_id, $teacherMap) ? $teacherMap[$model->teacher_id] : '<span class="not-set">(не задано)</span>';
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
                    /* @var $model \common\models\Group */
                    $data = [];
                    for ($i = 0; $i < 7; $i++) {
                        if ($model->weekday[$i] == '1') {
                            $data[] = '<nobr>' . \common\components\helpers\Calendar::$weekDaysShort[($i + 1) % 7] . ": {$model->scheduleData[$i]}</nobr>";
                        }
                    }
                    return implode('<br>', $data);
                },
            ],
            [
                'format' => 'text',
                'header' => 'Ученики',
                'content' => function ($model, $key, $index, $column) {
                    return '<div class="pupils"><nobr>' . count($model->activeGroupPupils) . ' учеников '
                        . '<a href="#" onclick="$(this).closest(\'.pupils\').find(\'.pupil_list\').toggle(); return false;"><span class="glyphicon glyphicon-chevron-down"></span></a></nobr>'
                        . '<div class="pupil_list" style="display: none;">'
                        . implode('<br>', \yii\helpers\ArrayHelper::getColumn($model->activeGroupPupils, 'user.name'))
                        . '</div></div>';
                },
            ],
            [
                'class' => \yii\grid\ActionColumn::class,
                'template' => $canEdit ? '{update}' : '',
                'buttonOptions' => ['class' => 'btn btn-default'],

            ],
        ],
    ]); ?>
</div>
