<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $title string */

$this->title = isset($title) ? $title : 'ВУЗы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="teacher-index">
    <div class="pull-right"><a href="<?= \yii\helpers\Url::to(['page']); ?>">Настройки страницы</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::a($model->name, ['update', 'id' => $model->id]);
                },
            ],
            [
                'attribute' => 'active',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::activeCheckbox($model, 'active', ['label' => null, 'onchange' => 'Main.changeEntityActive("high-school", ' . $model->id . ', this);']);
                },
                'options' => [
                    'align' => 'center',
                ],
            ],
            [
                'class' => \yii\grid\ActionColumn::class,
                'template' => '{delete}',
                'buttonOptions' => ['class' => 'btn btn-default'],
            ],
        ],
        'rowOptions' => function ($model, $key, $index, $grid) {if ($model->active == \common\models\HighSchool::STATUS_INACTIVE) return ['class' => 'inactive']; else return [];},
    ]); ?>
</div>
