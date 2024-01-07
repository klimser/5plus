<?php

use yii\bootstrap4\Html;
use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Тесты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="quiz-index">
    <div class="float-right"><a href="<?= Url::to(['page']); ?>">Настройки страницы</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать тест', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'showHeader' => false,
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'layout' => "{items}\n{pager}",
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::a(
                        $model->name . ' (<small>' . $model->subject->subjectCategory->name . ' / ' . $model->subject->name['ru'] . '</small>)',
                        Url::to(['update', 'id' => $model->id])
                    );
                }
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{delete}',
                'buttons' => [
                    'delete' =>  function($url,$model) {
                        return Html::a('<span class="fas fa-trash-alt"></span>', $url, [
                            'title' => Yii::t('yii', 'Delete'),
                            'class' => 'btn btn-outline-dark',
                            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

</div>
