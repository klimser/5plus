<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Блог';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subject-index">
    <div class="float-right"><a href="<?= \yii\helpers\Url::to(['page']); ?>">Настройки страницы списка постов</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить пост', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return  = [];
            if (!$model->active) {
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
            'created_at',
            [
                'attribute' => 'active',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::activeCheckbox($model, 'active', ['label' => null, 'onchange' => 'Main.changeEntityActive("blog", ' . $model->id . ', this);']);
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
    ]); ?>
</div>
