<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \backend\models\UserSearch */
/* @var $firstLetter string */
/* @var $selectedYear int */
/* @var $isRoot bool */

$this->title = 'Пользователи';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить студента', ['create-pupil'], ['class' => 'btn btn-success']) ?>
        <?php /*if ($isRoot): ?>
            <?= Html::a('Добавить сотрудника', ['create-employee'], ['class' => 'btn btn-success pull-right']) ?>
        <?php endif;*/ ?>
    </p>
    <nav aria-label="User by letter" class="text-center">
        <ul class="pagination">
            <li<?php if ($selectedYear < 0): ?> class="active"<?php endif; ?>>
                <?php if ($selectedYear < 0): ?>
                    <span>Все</span>
                <?php else: ?>
                    <a href="<?= \yii\helpers\Url::to(['user/index', 'letter' => $firstLetter, 'year' => -1, 'page' => 1]); ?>">Все</a>
                <?php endif; ?>
            </li>
            <?php foreach (\backend\components\UserComponent::getStartYears() as $year): ?>
                <li<?php if ($year == $selectedYear): ?> class="active"<?php endif; ?>>
                    <?php if ($year == $selectedYear): ?>
                        <span><?= $year; ?></span>
                    <?php else: ?>
                        <a href="<?= \yii\helpers\Url::to(['user/index', 'letter' => $firstLetter, 'year' => $year, 'page' => 1]); ?>"><?= $year; ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <nav aria-label="User by letter" class="text-center">
        <ul class="pagination">
            <li<?php if ($firstLetter == 'ALL'): ?> class="active"<?php endif; ?>>
                <?php if ($firstLetter == 'ALL'): ?>
                    <span>Все</span>
                <?php else: ?>
                    <a href="<?= \yii\helpers\Url::to(['user/index', 'letter' => 'ALL', 'year' => $selectedYear, 'page' => 1]); ?>">Все</a>
                <?php endif; ?>
            </li>
            <?php foreach (\backend\components\UserComponent::getFirstLetters() as $letter): ?>
                <li<?php if ($letter == $firstLetter): ?> class="active"<?php endif; ?>>
                    <?php if ($letter == $firstLetter): ?>
                        <span><?= $letter; ?></span>
                    <?php else: ?>
                        <a href="<?= \yii\helpers\Url::to(['user/index', 'letter' => $letter, 'year' => $selectedYear, 'page' => 1]); ?>"><?= $letter; ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
        $roles = [null => 'Все'];
        foreach (\backend\models\User::$roleLabels as $key => $val) $roles[$key] = $val;
    ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'options' => ['class' => 'grid-view table-responsive'],
        'rowOptions' => function ($model, $key, $index, $grid) {return ($model->status == \backend\models\User::STATUS_LOCKED) ? ['class' => 'inactive'] : [];},
        'columns' => [
//            'username',
            'name',
            'phoneFull',
            [
                'attribute' => 'money',
                'contentOptions' => function ($model, $key, $index, $column) {
                    return ($model->role == \backend\models\User::ROLE_PUPIL && $model->debt) ? ['class' => 'danger'] : [];
                },
                'content' => function ($model, $key, $index, $column) {
                    if ($model->role != \backend\models\User::ROLE_PUPIL) return '';
                    return $model->debt ? 'Долг ' . ($model->debt->amount * (-1)) : $model->balance;
                },
            ],
            [
                'attribute' => 'role',
                'format' => 'html',
                'content' => function ($model, $key, $index, $column) {return \backend\models\User::$roleLabels[$model->role];},
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'role',
                    $roles,
                    ['class' => 'form-control']
                )
            ],
            [
                'class' => \yii\grid\ActionColumn::class,
                'template' => '<nobr>{update}{lock}{money_income}{payment_history}</nobr>',
                'buttons' => [
                    'lock' => function ($url, $model, $key) {
                        return $model->status == \backend\models\User::STATUS_ACTIVE
                            ? Html::button(Html::tag('span', '', ['class' => 'fas fa-lock']), ['onclick' => 'Main.changeEntityActive("user", ' . $model->id . ', this, 0);', 'class' => 'btn btn-default margin-right-10', 'type' => 'button', 'title' => 'Заблокировать'])
                            : Html::button(Html::tag('span', '', ['class' => 'fas fa-lock-open']), ['onclick' => 'Main.changeEntityActive("user", ' . $model->id . ', this, 1);', 'class' => 'btn btn-default margin-right-10', 'type' => 'button', 'title' => 'Разблокировать']);
                    },
                    'money_income' => function ($url, $model, $key) {
                        if ($model->role != \backend\models\User::ROLE_PUPIL) return '';
                        return Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-usd']), \yii\helpers\Url::to(['money/income', 'user' => $model->id]), ['class' => 'btn btn-default margin-right-10', 'title' => 'Внести деньги']);
                    },
                    'payment_history' => function ($url, $model, $key) {
                        if ($model->role != \backend\models\User::ROLE_PUPIL) return '';
                        return Html::a(Html::tag('span', '', ['class' => 'glyphicon glyphicon-list-alt']), \yii\helpers\Url::to(['money/payment', 'PaymentSearch' => ['user_id' => $model->id]]), ['class' => 'btn btn-default margin-right-10', 'title' => 'История платежей']);
                    },
                ],
                'buttonOptions' => ['class' => 'btn btn-default margin-right-10'],
            ],
        ],
    ]); ?>
</div>