<?php

use yii\bootstrap4\LinkPager;
use yii\grid\ActionColumn;
use yii\bootstrap4\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $createAllowed bool */

$this->title = 'Рассылки';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if ($createAllowed): ?>
        <p>
            <?= Html::a('Создать рассылку', ['create'], ['class' => 'btn btn-success']) ?>
        </p>
    <?php endif; ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'pager' => ['class' => LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'columns' => [
            [
                'attribute' => 'message_image',
                'format' => 'html',
                'contentOptions' => ['style' => 'width: 20%;'],
                'content' => function ($model, $key, $index, $column) {
                    return $model->message_image ? Html::img(Yii::getAlias('@uploadsUrl') . $model->message_image, ['class' => 'max-width-100']) : '-';
                },
            ],
            [
                'attribute' => 'message_text',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {
                    return Html::a(nl2br($model->message_text), ['view', 'id' => $model->id]);
                },
            ],
            [
                'attribute' => 'created_at',
                'content' => function ($model, $key, $index, $column) {
                    $content = $model->createDate->format('d.m.Y H:i');
                    if ($model->admin_id) {
                        $content .= '<br>' . $model->admin->name;
                    }
                    return $content;
                }
            ],
            [
                'attribute' => 'started_at',
                'format' => 'datetime',
            ],
            [
                'attribute' => 'finished_at',
                'format' => 'datetime',
            ],
            [
                'attribute' => 'data',
                'header' => 'Результат',
                'content' => function ($model, $key, $index, $column) {
                    $content = '';
                    if ($model->processResult) {
                        $content .= 'Успешно: ' . $model->processResult['success'];
                        if ($model->processResult['error']) {
                            $content .= '<br>Неуспешно: ' . $model->processResult['error'];
                        }
                    }
                    return $content;
                }
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{delete}',
                'buttons' => [
                    'delete' => function ($url, $model, $key) {
                        if (!$model->started_at || $model->startDate <= (new \DateTime())) {
                            return '';
                        }
                        
                        $title = Yii::t('yii', 'Delete');
                        $options = [
                            'class' => 'btn btn-outline-dark',
                            'title' => $title,
                            'aria-label' => $title,
                            'data-pjax' => '0',
                            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                        ];
                        return Html::a(Html::tag('span', '', ['class' => 'far fa-trash-alt']), $url, $options);
                    },
                ],
            ],
        ],
    ]); ?>

</div>
