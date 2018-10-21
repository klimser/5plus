<?php

/* @var $this yii\web\View */

$this->title = 'Панель управления';
?>
<div class="row">
    <a href="<?= \yii\helpers\Url::to(['page/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-file"></span> Страницы</a>
    <a href="<?= \yii\helpers\Url::to(['menu/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-th-list"></span> Меню</a>
    <a href="<?= \yii\helpers\Url::to(['subject/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-bullhorn"></span> Курсы</a>
    <a href="<?= \yii\helpers\Url::to(['subject-category/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-briefcase"></span> Группы курсов</a>
    <a href="<?= \yii\helpers\Url::to(['widget-html/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-cog"></span> Блоки</a>
    <a href="<?= \yii\helpers\Url::to(['high-school/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-education"></span> ВУЗы</a>
    <a href="<?= \yii\helpers\Url::to(['lyceum/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-education"></span> Лицеи</a>
</div>
<hr>
<div class="row">
    <a href="<?= \yii\helpers\Url::to(['order/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-book"></span> Заявки</a>
    <a href="<?= \yii\helpers\Url::to(['review/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-book"></span> Отзывы</a>
    <a href="<?= \yii\helpers\Url::to(['feedback/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-book"></span> Обратная связь</a>
</div>
<hr>
<div class="row">
    <a href="<?= \yii\helpers\Url::to(['user/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-user"></span> Пользователи</a>
    <a href="<?= \yii\helpers\Url::to(['teacher/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="fas fa-user-tie"></span> Учителя</a>
    <a href="<?= \yii\helpers\Url::to(['group/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="fas fa-users"></span> Группы</a>
    <a href="<?= \yii\helpers\Url::to(['event/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-list-alt"></span> Расписание</a>
    <a href="<?= \yii\helpers\Url::to(['user/schedule']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-list-alt"></span> Дневники</a>
</div>
<hr>
<div class="row">
    <a href="<?= \yii\helpers\Url::to(['quiz/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="icon icon-clipboard"></span> Тесты</a>
    <a href="<?= \yii\helpers\Url::to(['quiz-result/index']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="icon icon-clipboard"></span> Результаты тестов</a>
</div>
<hr>
<div class="row">
    <a href="<?= \yii\helpers\Url::to(['money/debt']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-usd"></span> Долги</a>
    <a href="<?= \yii\helpers\Url::to(['money/income']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-usd"></span> Внести платёж</a>
    <a href="<?= \yii\helpers\Url::to(['money/payment']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-usd"></span> Платежи</a>
    <a href="<?= \yii\helpers\Url::to(['money/actions']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-usd"></span> Действия</a>
    <a href="<?= \yii\helpers\Url::to(['money/salary']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-usd"></span> Зарплата</a>
</div>