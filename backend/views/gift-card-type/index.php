<?php

use yii\bootstrap4\Html;
use yii\bootstrap4\LinkPager;
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
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return  = [];
            if (!$model->active) {
                $return['class'] = 'table-warning';
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
                            'class' => 'btn btn-outline-dark',
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
