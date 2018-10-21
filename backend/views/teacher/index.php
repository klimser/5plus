<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Учителя';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="teacher-index">
    <div class="pull-right"><a href="<?= \yii\helpers\Url::to(['page']); ?>">Настройки страницы учителей</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить учителя', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return  = [];
            if ($model->page_visibility == \common\models\Teacher::STATUS_INACTIVE) {
                $return['class'] = 'warning';
            }
            return $return;
        },
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::a($model->name, ['update', 'id' => $model->id]);
                },
            ],
            [
                'label' => 'Предметы',
                'content' => function ($model, $key, $index, $column) {
                    $content = '';
                    foreach ($model->subjects as $subject) $content .= $subject->name . "<br>\n";
                    return $content;
                }
            ],
            [
                'attribute' => 'active',
                'label' => 'Уволить/Нанять',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::button(
                            $model->active ? '<span class="glyphicon glyphicon-ban-circle"></span>' : '<span class="glyphicon glyphicon-leaf"></span>',
                            [
                                'class' => 'btn btn-default',
                                'onclick' => 'Main.changeEntityActive("teacher", ' . $model->id . ', this, ' . ($model->active ? 0 : 1) . ');',
                                'title' => $model->active ? 'Уволить' : 'Нанять',
                            ]
                    );
                },
                'options' => [
                    'align' => 'center',
                ],
            ],
        ],
    ]); ?>
</div>
