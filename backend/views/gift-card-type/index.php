<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Типы предоплаченных карт';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="gift-card-type-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
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
            'name',
            'amount',
            [
                'attribute' => 'active',
                'label' => 'Доступно для покупки',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return
                        Html::tag('span', $model->active ? 'да' : 'нет', ['class' => 'label label-' . ($model->active ? 'primary' : 'warning')]) . ' ' .
                        Html::button(
                        $model->active ? '<span class="fas fa-minus-circle"></span>' : '<span class="fas fa-plus-circle"></span>',
                        [
                            'class' => 'btn btn-default',
                            'onclick' => 'Main.changeEntityActive("gift-card-type", ' . $model->id . ', this, ' . ($model->active ? 0 : 1) . ');',
                            'title' => $model->active ? 'Запретить' : 'Разрешить',
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
