<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \backend\models\DebtSearch */
/* @var $debtorMap \backend\models\User[] */

$this->title = 'Задолженности';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="debt-index">
    <div class="pull-right"><a href="<?= \yii\helpers\Url::to(['money/income']); ?>" class="btn btn-info">Внести оплату</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return = [];
            $nowDate = new \DateTime();
            if ($nowDate > $model->dangerDate) $return['class'] = 'danger';
            return $return;
        },
        'columns' => [
            [
                'attribute' => 'user_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->user->name;
                },
//                'options' => ['class' => 'col-xs-1'],
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'user_id',
                    $debtorMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'amount',
//                'options' => ['class' => 'col-xs-2'],
            ],
            [
                'attribute' => 'comment',
//                'options' => ['class' => 'col-xs-2'],
            ],
            [
                'attribute' => 'danger_date',
                'format' => 'date',
                'label' => 'Дата недопуска',
                'filter' => \dosamigos\datepicker\DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'dangerDateString',
                    'template' => '{addon}{input}',
                    'clientOptions' => [
                        'weekStart' => 1,
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                    ],
                ]),
//                'options' => ['class' => 'col-xs-2'],
            ],
            [
                'content' => function ($model, $key, $index, $column) {
                    return Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-usd', 'title' => 'Внести оплату']), \yii\helpers\Url::to(['money/income', 'user' => $model->user_id]));
                },
            ],
        ],
    ]); ?>
</div>