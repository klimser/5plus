<?php

use common\components\helpers\Calendar;
use common\components\helpers\WordForm;
use common\models\Group;
use common\models\GroupSearch;
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
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $key, $index, $grid) {return ($model->active == Group::STATUS_INACTIVE) ? ['class' => 'table-secondary'] : [];},
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
                'header' => 'Студенты',
                'content' => function ($model, $key, $index, $column) {
                    return '<div class="pupils"><span class="text-nowrap">' . count($model->activeGroupPupils) . ' ' . WordForm::getPupilsForm(count($model->activeGroupPupils)) . ' '
                        . '<button type="button" class="btn btn-link" onclick="$(this).closest(\'.pupils\').find(\'.pupil_list\').collapse(\'toggle\');">'
                        . '<span class="fas fa-chevron-down"></span>'
                        . '</button></span>'
                        . '<ul class="list-group pupil_list collapse"><li class="list-group-item p-1">'
                        . implode('</li><li class="list-group-item p-1">', ArrayHelper::getColumn($model->activeGroupPupils, 'user.name'))
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
</div>
