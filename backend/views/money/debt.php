<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $canCorrect bool */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \common\models\DebtSearch */
/* @var $debtorMap \common\models\User[] */
/* @var $groups \common\models\Group[] */

$this->title = 'Задолженности';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="debt-index">
    <div class="pull-right"><a href="<?= \yii\helpers\Url::to(['money/income']); ?>" class="btn btn-info">Внести оплату</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'user_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->user->name;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'user_id',
                    $debtorMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'group_id',
                'content' => function ($model, $key, $index, $column) {
                    return $model->group->name;
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'group_id',
                    array_merge([null => 'Любая'], \yii\helpers\ArrayHelper::map($groups, 'id', 'name')),
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'amount',
                'filter' => \kartik\field\FieldRange::widget([
                    'model' => $searchModel,
                    'attribute1' => 'amountFrom',
                    'attribute2' => 'amountTo',
//                    'name1'=>'amountFrom',
//                    'name2'=>'amountTo',
                    'separator' => '-',
                    'template' => '{widget}',
                    'type' => \kartik\field\FieldRange::INPUT_TEXT,
                ]),
                'contentOptions' => ['class' => 'text-right'],
            ],
            [
                'attribute' => 'created_at',
                'format' => 'date',
            ],
            [
                'content' => function ($model, $key, $index, $column) use ($canCorrect) {
                    return Html::a(Html::tag('span', '', ['class' => 'fas fa-dollar-sign']), \yii\helpers\Url::to(['money/income', 'user' => $model->user_id]), ['class' => 'btn btn-default margin-right-10', 'title' => 'Внести деньги'])
                        . ($canCorrect ? Html::a(Html::tag('span', '', ['class' => 'fas fa-fire-extinguisher']), \yii\helpers\Url::to(['money/correction', 'userId' => $model->user_id, 'groupId' => $model->group_id]), ['class' => 'btn btn-default', 'title' => 'Погасить долг']) : '');
                },
            ],
        ],
    ]); ?>
</div>