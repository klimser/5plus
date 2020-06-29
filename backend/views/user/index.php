<?php

use backend\components\UserComponent;
use common\models\User;
use common\models\UserSearch;
use yii\grid\ActionColumn;
use yii\bootstrap4\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\web\View;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel UserSearch */
/* @var $firstLetter string */
/* @var $selectedYear int */
/* @var $canManageEmployees bool */

$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить студента', ['create-pupil'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Добавить учителя', ['create-teacher'], ['class' => 'btn btn-success float-right']) ?>
        <?php if ($canManageEmployees): ?>
            <?= Html::a('Добавить сотрудника', ['create-employee'], ['class' => 'btn btn-success float-right mr-2']) ?>
        <?php endif; ?>
    </p>
    <nav aria-label="User by year">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php if ($selectedYear < 0): ?> active <?php endif; ?>">
                <?php if ($selectedYear < 0): ?>
                    <span class="page-link">Все</span>
                <?php else: ?>
                    <a class="page-link" href="<?= Url::to(['user/index', 'letter' => $firstLetter, 'year' => -1, 'page' => 1]); ?>">Все</a>
                <?php endif; ?>
            </li>
            <?php foreach (UserComponent::getStartYears() as $year): ?>
                <li class="page-item <?php if ($year == $selectedYear): ?> active <?php endif; ?>">
                    <?php if ($year == $selectedYear): ?>
                        <span class="page-link"><?= $year; ?></span>
                    <?php else: ?>
                        <a class="page-link" href="<?= Url::to(['user/index', 'letter' => $firstLetter, 'year' => $year, 'page' => 1]); ?>"><?= $year; ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="row justify-content-start no-gutters">
        <?php if ($firstLetter == 'ALL'): ?>
            <span class="col-auto btn btn-primary rounded-0 px-2 mb-1">Все</span>
        <?php else: ?>
            <a class="col-auto btn btn-outline-primary rounded-0 px-2 mb-1" href="<?= Url::to(['user/index', 'letter' => 'ALL', 'year' => $selectedYear, 'page' => 1]); ?>">Все</a>
        <?php endif; ?>

        <?php foreach (UserComponent::getFirstLetters() as $letter): ?>
            <?php if ($letter == $firstLetter): ?>
                <span class="col-auto btn btn-primary rounded-0 px-3 py-2 border-left-0 mb-1"><?= $letter; ?></span>
            <?php else: ?>
                <a class="col-auto btn btn-outline-primary rounded-0 px-3 py-2 border-left-0 mb-1" href="<?= Url::to(['user/index', 'letter' => $letter, 'year' => $selectedYear, 'page' => 1]); ?>"><?= $letter; ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
        $roles = [null => 'Все'];
        foreach (UserComponent::ROLE_LABELS as $key => $val) $roles[$key] = $val;
    ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager' => ['class' => \yii\bootstrap4\LinkPager::class, 'listOptions' => ['class' => 'pagination justify-content-center']],
        'options' => ['class' => 'grid-view table-responsive'],
        'rowOptions' => function ($model, $key, $index, $grid) {return ($model->status == User::STATUS_LOCKED) ? ['class' => 'table-secondary'] : [];},
        'columns' => [
//            'username',
            [
                'attribute' => 'name',
                'content' => function ($model, $key, $index, $column) {
                    $addon = '';
                    if (!empty($model->note)) {
                        $addon = '<br><small>' . nl2br($model->note) . '</small>';
                    }
                    return "$model->name $addon";
                },
            ],
            'phoneFull',
            [
                'attribute' => 'money',
                'contentOptions' => function ($model, $key, $index, $column) {
                    return ($model->role == User::ROLE_PUPIL && $model->money < 0) ? ['class' => 'danger'] : [];
                },
                'content' => function ($model, $key, $index, $column) {
                    if ($model->role != User::ROLE_PUPIL) return '';
                    return $model->money < 0 ? 'Долг ' . ($model->money * (-1)) : $model->money;
                },
            ],
            [
                'attribute' => 'role',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {return UserComponent::ROLE_LABELS[$model->role];},
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'role',
                    $roles,
                    ['class' => 'form-control']
                )
            ],
            [
                'class' => ActionColumn::class,
                'template' => '<span class="text-nowrap">{update}{lock}{money_income}{payment_history}</span>',
                'buttons' => [
                    'update' =>  function($url,$model) {
                        return Html::a('<span class="fas fa-pencil-alt"></span>', $url, [
                            'title' => Yii::t('app', 'update'),
                            'class' => 'btn btn-outline-dark',
                        ]);
                    },
                    'lock' => function ($url, $model, $key) {
                        return $model->status == User::STATUS_ACTIVE
                            ? Html::button(Html::tag('span', '', ['class' => 'fas fa-lock']), ['onclick' => 'Main.changeEntityActive("user", ' . $model->id . ', this, 0);', 'class' => 'btn btn-outline-dark ml-2', 'type' => 'button', 'title' => 'Заблокировать'])
                            : Html::button(Html::tag('span', '', ['class' => 'fas fa-lock-open']), ['onclick' => 'Main.changeEntityActive("user", ' . $model->id . ', this, 1);', 'class' => 'btn btn-outline-dark ml-2', 'type' => 'button', 'title' => 'Разблокировать']);
                    },
                    'money_income' => function ($url, $model, $key) {
                        if ($model->role != User::ROLE_PUPIL) return '';
                        return Html::a(Html::tag('span', '', ['class' => 'fas fa-dollar-sign']), Url::to(['money/income', 'user' => $model->id]), ['class' => 'btn btn-outline-dark ml-2', 'title' => 'Внести деньги']);
                    },
                    'payment_history' => function ($url, $model, $key) {
                        if ($model->role != User::ROLE_PUPIL) return '';
                        return Html::a(Html::tag('span', '', ['class' => 'fas fa-list-alt']), Url::to(['money/payment', 'PaymentSearch' => ['user_id' => $model->id]]), ['class' => 'btn btn-outline-dark ml-2', 'title' => 'История платежей']);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
