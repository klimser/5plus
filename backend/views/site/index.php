<?php

use \yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $admin \yii\web\User */
/* @var $orderCount int */
/* @var $feedbackCount int */
/* @var $reviewCount int */

$this->title = 'Панель управления';
?>
<div class="row">
    <div class="col-xs-12">
        <a class="btn btn-default btn-lg full-width" href="<?= Url::to('dashboard/index'); ?>">
            <span class="fas fa-tachometer-alt fa-3x"></span><hr>
            Панель управления
        </a>
    </div>
</div>
<hr>
<div class="row">
    <?php if ($admin->can('moneyManagement')): ?>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <a class="btn btn-default btn-lg full-width" href="<?= Url::to('money/income'); ?>">
                <span class="fas fa-hand-holding-usd fa-3x"></span><hr>
                Принять оплату
            </a>
        </div>
    <?php endif; ?>
    <?php if ($admin->can('contractManagement')): ?>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <a class="btn btn-default btn-lg full-width" href="<?= Url::to('contract/create'); ?>">
                <span class="fas fa-file-invoice-dollar fa-3x"></span><hr>
                Выдать договор
            </a>
        </div>
    <?php endif; ?>
    <?php if ($admin->can('manageSchedule')): ?>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <a class="btn btn-default btn-lg full-width" href="<?= Url::to('event/index'); ?>">
                <span class="far fa-calendar-alt fa-3x"></span><hr>
                Расписание
            </a>
        </div>
    <?php endif; ?>
    <?php if ($admin->can('welcomeLessons')): ?>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <a class="btn btn-default btn-lg full-width" href="<?= Url::to('welcome-lesson/index'); ?>">
                <span class="fas fa-flag-checkered fa-3x"></span><hr>
                Пробные уроки
            </a>
        </div>
    <?php endif; ?>
</div>

<hr>

<div class="row">
    <div class="col-xs-12 col-md-9">
        <?php if ($admin->can('viewGroups') || $admin->can('content')): ?>
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="row">
                        <?php if ($admin->can('viewGroups')): ?>
                            <div class="col-xs-12 col-sm-4 col-md-3">
                                <a class="btn btn-default btn-lg full-width" href="<?= Url::to('group/index'); ?>">
                                    <span class="fas fa-users fa-2x"></span><br>
                                    Группы
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($admin->can('manageTeachers')): ?>
                            <div class="col-xs-12 col-sm-4 col-md-3">
                                <a class="btn btn-default btn-lg full-width" href="<?= Url::to('teacher/index'); ?>">
                                    <span class="fas fa-user-tie fa-2x"></span><br>
                                    Учителя
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($admin->can('manageSubjectCategories')): ?>
                            <div class="col-xs-12 col-sm-4 col-md-3">
                                <a class="btn btn-default btn-lg full-width" href="<?= Url::to('subject-category/index'); ?>">
                                    <span class="fas fa-briefcase fa-2x"></span><br>
                                    Направления
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($admin->can('manageSubjects')): ?>
                            <div class="col-xs-12 col-sm-4 col-md-3">
                                <a class="btn btn-default btn-lg full-width" href="<?= Url::to('subject/index'); ?>">
                                    <span class="fas fa-chalkboard-teacher fa-2x"></span><br>
                                    Курсы
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <hr>
        <?php endif; ?>

        <?php if ($admin->can('support')): ?>
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-12 col-sm-4 col-md-3">
                            <a class="btn btn-default btn-lg full-width <?= $orderCount > 0 ? 'btn-warning' : ''; ?>" href="<?= Url::to('order/index'); ?>">
                                <span class="fas fa-book fa-2x"></span><br>
                                Заявки <?php if ($orderCount > 0): ?>(<?= $orderCount; ?>)<?php endif; ?>
                            </a>
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-3">
                            <a class="btn btn-default btn-lg full-width <?= $feedbackCount > 0 ? 'btn-warning' : ''; ?>" href="<?= Url::to('feedback/index'); ?>">
                                <span class="fas fa-book fa-2x"></span><br>
                                Обратная связь <?php if ($feedbackCount > 0): ?>(<?= $feedbackCount; ?>)<?php endif; ?>
                            </a>
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-3">
                            <a class="btn btn-default btn-lg full-width <?= $reviewCount > 0 ? 'btn-warning' : ''; ?>" href="<?= Url::to('review/index'); ?>">
                                <span class="fas fa-book fa-2x"></span><br>
                                Отзывы <?php if ($reviewCount > 0): ?>(<?= $reviewCount; ?>)<?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
        <?php endif; ?>
    </div>
    <div class="col-xs-12 col-md-3">
        <div class="panel panel-default">
            <ul class="nav nav-pills nav-stacked">
                <?php if ($admin->can('manageUsers')): ?>
                    <li role="presentation">
                        <a href="<?= Url::to(['user/index']); ?>">
                            <span class="fas fa-user"></span> Пользователи
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($admin->can('cashier')): ?>
                    <li role="presentation">
                        <a href="<?= Url::to(['contract/index']); ?>">
                            <span class="fas fa-file-invoice-dollar"></span> Договоры
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="<?= Url::to(['money/payment']); ?>">
                            <span class="fas fa-dollar-sign"></span> Платежи
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="<?= Url::to(['money/debt']); ?>">
                            <span class="fas fa-dollar-sign"></span> Долги
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="<?= Url::to(['money/actions']); ?>">
                            <span class="fas fa-clipboard-list"></span> Действия
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($admin->can('viewMissed')): ?>
                    <li role="presentation" class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                            Посещаемость <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li role="presentation">
                                <a href="<?= Url::to(['missed/table']); ?>">
                                    <span class="fas fa-table"></span> Таблица посещений
                                </a>
                            </li>
                            <?php if ($admin->can('callMissed')): ?>
                                <li role="presentation">
                                    <a href="<?= Url::to(['missed/list']); ?>">
                                        <span class="fas fa-phone"></span> Обзвон отсутствующих
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($admin->can('viewSalary')): ?>
                    <li role="presentation">
                        <a href="<?= Url::to(['money/salary']); ?>">
                            <span class="fas fa-money-bill-wave"></span> Зарплата
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($admin->can('root')): ?>
                    <li role="presentation">
                        <a href="<?= Url::to(['company/index']); ?>">
                            <span class="fas fa-building"></span> Компании
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($admin->can('moneyManagement') || $admin->can('manageGiftCardTypes')): ?>
                    <li role="presentation" class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                            Предоплаченные карты <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($admin->can('moneyManagement')): ?>
                                <li role="presentation">
                                    <a href="<?= Url::to(['gift-card/index']); ?>">
                                        <span class="fas fa-file-invoice"></span> Список карт
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($admin->can('manageGiftCardTypes')): ?>
                                <li role="presentation">
                                    <a href="<?= Url::to(['gift-card-type/index']); ?>">
                                        <span class="fas fa-cog"></span> Типы карт
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($admin->can('reportGroupMovement')
                    || $admin->can('reportDebt')
                    || $admin->can('reportMoney')
                    || $admin->can('reportCash')
                ): ?>
                    <li role="presentation" class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                            Отчёты <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($admin->can('reportGroupMovement')): ?>
                                <li role="presentation">
                                    <a href="<?= Url::to(['report/group-movement']); ?>">
                                        <span class="fas fa-walking"></span> Отчет движения
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($admin->can('reportDebt')): ?>
                                <li role="presentation">
                                    <a href="<?= Url::to(['report/debt']); ?>">
                                        <span class="fas fa-info"></span> Отчет по должникам
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($admin->can('reportMoney')): ?>
                                <li role="presentation">
                                    <a href="<?= Url::to(['report/money']); ?>">
                                        <span class="fas fa-dollar-sign"></span> Финансовый отчёт
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($admin->can('reportCash')): ?>
                                <li role="presentation">
                                    <a href="<?= Url::to(['report/cash']); ?>">
                                        <span class="fas fa-coins"></span> Касса
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="<?= Url::to(['report/rest-money']); ?>">
                                        <span class="fas fa-funnel-dollar"></span> Остатки
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($admin->can('content')): ?>
                    <li role="presentation" class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                            Контент <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li role="presentation">
                                <a href="<?= Url::to(['page/index']); ?>">
                                    <span class="fas fa-file"></span> Страницы
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="<?= Url::to(['menu/index']); ?>">
                                    <span class="fas fa-bars"></span> Меню
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="<?= Url::to(['widget-html/index']); ?>">
                                    <span class="fas fa-cog"></span> Блоки
                                </a>
                            </li>
                            <li class="divider" role="separator"></li>
                            <li role="presentation">
                                <a href="<?= Url::to(['high-school/index']); ?>">
                                    <span class="fas fa-graduation-cap"></span> ВУЗы
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="<?= Url::to(['lyceum/index']); ?>">
                                    <span class="fas fa-landmark"></span> Лицеи
                                </a>
                            </li>
                            <li class="divider" role="separator"></li>
                            <li role="presentation">
                                <a href="<?= Url::to(['promotion/index']); ?>">
                                    <span class="fas fa-bell"></span> Акции
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="<?= Url::to(['blog/index']); ?>">
                                    <span class="far fa-newspaper"></span> Блог
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li role="presentation">
                        <a href="<?= Url::to(['quiz/index']); ?>">
                            <span class="fas fa-clipboard"></span> Тесты
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="<?= Url::to(['quiz-result/index']); ?>">
                            <span class="fas fa-clipboard-list"></span> Результаты тестов
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($admin->can('manager')): ?>
                    <li role="presentation">
                        <a href="<?= Url::to(['bot-mailing/index']); ?>">
                            <span class="fas fa-mail-bulk"></span> Рассылки 
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

    <?php
/*        <a href="<?= \yii\helpers\Url::to(['user/schedule']); ?>" class="btn btn-default btn-lg col-xs-12 col-sm-4 col-md-3 col-lg-2"><span class="glyphicon glyphicon-list-alt"></span> Дневники</a>*/
?>
