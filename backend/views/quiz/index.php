<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Тесты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="quiz-index">
    <div class="float-right"><a href="<?= \yii\helpers\Url::to(['page']); ?>">Настройки страницы</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать тест', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'showHeader' => false,
        'layout' => "{items}\n{pager}",
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::a(
                        $model->name . ' (<small>' . $model->subject->subjectCategory->name . ' / ' . $model->subject->name . '</small>)',
                        \yii\helpers\Url::to(['update', 'id' => $model->id])
                    );
                }
            ],
            [
                'class' => \yii\grid\ActionColumn::class,
                'template' => '{delete}',
                'buttonOptions' => ['class' => 'btn btn-default'],
            ],
        ],
    ]); ?>

</div>
