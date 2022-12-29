<?php

use common\models\Debt;
use kartik\field\FieldRange;
use yii\bootstrap4\Html;
use yii\bootstrap4\LinkPager;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $canCorrect bool */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \common\models\DebtSearch */
/* @var $debtorMap \common\models\User[] */
/* @var $courseMap \common\models\Course[] */

$this->title = 'Задолженности';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="debt-index">
    <div class="float-right"><a href="<?= Url::to(['money/income']); ?>" class="btn btn-info">Внести оплату</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
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
                'attribute' => 'course_id',
                'content' => static fn (Debt $model, $key, $index, $column) => $model->course->courseConfig->name,
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'course_id',
                    $courseMap,
                    ['class' => 'form-control']
                )
            ],
            [
                'attribute' => 'amount',
                'filter' => FieldRange::widget([
                    'model' => $searchModel,
                    'attribute1' => 'amountFrom',
                    'attribute2' => 'amountTo',
//                    'name1'=>'amountFrom',
//                    'name2'=>'amountTo',
                    'separator' => '-',
                    'template' => '{widget}',
                    'type' => FieldRange::INPUT_TEXT,
                ]),
                'contentOptions' => ['class' => 'text-right'],
            ],
            [
                'attribute' => 'created_at',
                'format' => 'date',
            ],
            [
                'header' => 'действия',
                'content' => function (Debt $model, $key, $index, $column) use ($canCorrect) {
                    return '<div class="text-nowrap">' . Html::a(Html::tag('span', '', ['class' => 'fas fa-dollar-sign']), Url::to(['money/income', 'user' => $model->user_id]), ['class' => 'btn btn-outline-dark', 'title' => 'Внести деньги'])
                        . ($canCorrect ? Html::a(Html::tag('span', '', ['class' => 'fas fa-fire-extinguisher']), Url::to(['money/correction', 'userId' => $model->user_id, 'courseId' => $model->course_id]), ['class' => 'btn btn-outline-dark ml-2', 'title' => 'Погасить долг']) : '')
                        . '</div>';
                },
            ],
        ],
    ]); ?>
</div>
