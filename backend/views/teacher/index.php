<?php

use common\models\Teacher;
use yii\grid\ActionColumn;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $deleteAllowed bool */

$this->title = 'Учителя';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="teacher-index">
    <div class="float-right"><a href="<?= Url::to(['page']); ?>">Настройки страницы учителей</a></div>
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить учителя', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'rowOptions' => function ($model, $index, $widget, $grid) {
            $return  = [];
            if ($model->page_visibility == Teacher::STATUS_INACTIVE) {
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
            [
                'label' => 'Предметы',
                'content' => function ($model, $key, $index, $column) {
                    $content = '';
                    foreach ($model->subjects as $subject) $content .= $subject->name . "<br>\n";
                    return $content;
                }
            ],
            [
                'class' => ActionColumn::class,
                'template' => '<span class="text-nowrap">{active}{user}' . ($deleteAllowed ? '{delete}' : '') . '</span>',
                'buttons' => [
                    'active' => function ($url, $model, $key) {
                        return $model->active === Teacher::STATUS_ACTIVE
                            ? Html::button(Html::tag('span', '', ['class' => 'fas fa-minus-circle']), ['onclick' => 'Main.changeEntityActive("teacher", ' . $model->id . ', this, 0);', 'class' => 'btn btn-default mr-2', 'type' => 'button', 'title' => 'Уволить'])
                            : Html::button(Html::tag('span', '', ['class' => 'fas fa-plus-circle']), ['onclick' => 'Main.changeEntityActive("teacher", ' . $model->id . ', this, 1);', 'class' => 'btn btn-default mr-2', 'type' => 'button', 'title' => 'Нанять']);
                    },
                    'user' => function ($url, $model, $key) {
                        if ($model->user) {
                            return Html::a(Html::tag('span', '', ['class' => 'fas fa-user']), Url::to(['user/update', 'id' => $model->user->id]), ['class' => 'btn btn-default mr-2', 'title' => 'Пользователь']);
                        }
                        return Html::a(Html::tag('span', '', ['class' => 'fas fa-user-plus']), Url::to(['user/create-teacher', 'teacher_id' => $model->id]), ['class' => 'btn btn-default mr-2', 'title' => 'Создать пользователя']);
                    },
                    'delete' => function ($url, $model, $key) {
                        if (!$model->deleteAllowed) return '';
                        $options = [
                            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                            'class' => 'btn btn-default',
                        ];
                        $icon = Html::tag('span', '', ['class' => "fas fa-times"]);
                        return Html::a($icon, $url, $options);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
