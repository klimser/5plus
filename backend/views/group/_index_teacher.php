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
/* @var $canEdit bool */;
?>
<div id="messages_place"></div>
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
            'format' => 'text',
            'header' => 'Тема',
            'content' => (fn($model, $key, $index, $column) => ($model->note ? $model->note->topic : '')),
        ],
        [
            'class' => ActionColumn::class,
            'template' => '{update}',
            'buttons' => [
                'update' =>  function($url,$model) {
                    return Html::a('<span class="fas fa-pencil-alt"></span>', $url, [
                        'title' => Yii::t('yii', 'Update'),
                        'class' => 'btn btn-outline-dark collapse show',
                        'onclick' => 'Group.toggleNoteUpdate(this); return false;',
                        'data-group-id' => $model->id,
                    ]);
                },
            ],
        ],
    ],
]); ?>
