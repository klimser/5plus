<?php

use \yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $admin \yii\web\User */
/* @var $orderCount int */
/* @var $feedbackCount int */
/* @var $reviewCount int */

$this->title = 'Панель управления';
?>
<?php if ($admin->can('manager')): ?>
    <div class="row">
        <div class="col-12">
            <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('dashboard/index'); ?>">
                <span class="fas fa-tachometer-alt fa-3x"></span><hr>
                Панель управления
            </a>
        </div>
    </div>
    <hr>
<?php endif; ?>
<div class="row">
    <?php if ($admin->can('accountant')): ?>
        <div class="col-6 col-md mb-3">
            <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('money/income'); ?>">
                <span class="fas fa-hand-holding-usd fa-3x"></span><hr>
                Принять оплату
            </a>
        </div>
    <?php endif; ?>
    <?php if ($admin->can('accountant')): ?>
        <div class="col-6 col-md mb-3">
            <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('contract/create'); ?>">
                <span class="fas fa-file-invoice-dollar fa-3x"></span><hr>
                Выдать договор
            </a>
        </div>
    <?php endif; ?>
    <?php if ($admin->can('manageSchedule')): ?>
        <div class="col-6 col-md mb-3">
            <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('event/index'); ?>">
                <span class="far fa-calendar-alt fa-3x"></span><hr>
                Расписание
            </a>
        </div>
    <?php endif; ?>
    <?php if ($admin->can('welcomeLessons')): ?>
        <div class="col-6 col-md mb-3">
            <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('welcome-lesson/index'); ?>">
                <span class="fas fa-flag-checkered fa-3x"></span><hr>
                Пробные уроки
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="row border-top pt-3">
    <div class="col-lg-9">
        <?php if ($admin->can('viewGroups') || $admin->can('content')): ?>
            <div class="row">
                <?php if ($admin->can('viewGroups')): ?>
                    <div class="col-6 col-md mb-3">
                        <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('group/index'); ?>">
                            <span class="fas fa-users fa-2x"></span><br>
                            Группы
                        </a>
                    </div>
                <?php endif; ?>
                <?php if ($admin->can('manageTeachers')): ?>
                    <div class="col-6 col-md mb-3">
                        <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('teacher/index'); ?>">
                            <span class="fas fa-user-tie fa-2x"></span><br>
                            Учителя
                        </a>
                    </div>
                <?php endif; ?>
                <?php if ($admin->can('manageSubjectCategories')): ?>
                    <div class="col-6 col-md mb-3">
                        <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('subject-category/index'); ?>">
                            <span class="fas fa-briefcase fa-2x"></span><br>
                            Направления
                        </a>
                    </div>
                <?php endif; ?>
                <?php if ($admin->can('manageSubjects')): ?>
                    <div class="col-6 col-md mb-3">
                        <a class="btn btn-outline-dark btn-lg btn-block" href="<?= Url::to('subject/index'); ?>">
                            <span class="fas fa-chalkboard-teacher fa-2x"></span><br>
                            Курсы
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($admin->can('support')): ?>
            <div class="row border-top pt-3">
                <div class="col-md mb-3">
                    <a class="btn btn-lg btn-block <?= $orderCount > 0 ? 'btn-warning' : 'btn-outline-dark'; ?>" href="<?= Url::to('order/index'); ?>">
                        <span class="fas fa-book fa-2x"></span><br>
                        Заявки <?php if ($orderCount > 0): ?>(<?= $orderCount; ?>)<?php endif; ?>
                    </a>
                </div>
                <div class="col-md mb-3">
                    <a class="btn btn-lg btn-block <?= $feedbackCount > 0 ? 'btn-warning' : 'btn-outline-dark'; ?>" href="<?= Url::to('feedback/index'); ?>">
                        <span class="fas fa-book fa-2x"></span><br>
                        Обратная связь <?php if ($feedbackCount > 0): ?>(<?= $feedbackCount; ?>)<?php endif; ?>
                    </a>
                </div>
                <div class="col-md mb-3">
                    <a class="btn btn-lg btn-block <?= $reviewCount > 0 ? 'btn-warning' : 'btn-outline-dark'; ?>" href="<?= Url::to('review/index'); ?>">
                        <span class="fas fa-book fa-2x"></span><br>
                        Отзывы <?php if ($reviewCount > 0): ?>(<?= $reviewCount; ?>)<?php endif; ?>
                    </a>
                </div>
            </div>
            <hr class="d-lg-none">
        <?php endif; ?>
    </div>
    <div class="col-lg-3">
        <ul class="nav nav-pills flex-column">
            <?php if ($admin->can('manageUsers')): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['user/index']); ?>">
                        <span class="fas fa-user"></span> Пользователи
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($admin->can('cashier')): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['contract/index']); ?>">
                        <span class="fas fa-file-invoice-dollar"></span> Договоры
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['money/payment']); ?>">
                        <span class="fas fa-dollar-sign"></span> Платежи
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['money/debt']); ?>">
                        <span class="fas fa-dollar-sign"></span> Долги
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['money/actions']); ?>">
                        <span class="fas fa-clipboard-list"></span> Действия
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($admin->can('viewMissed')): ?>
                <li class="nav-item dropdown" role="presentation">
                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                        Посещаемость <span class="caret"></span>
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="<?= Url::to(['missed/table']); ?>">
                            <span class="fas fa-table"></span> Таблица посещений
                        </a>
                        <?php if ($admin->can('callMissed')): ?>
                            <a class="dropdown-item" href="<?= Url::to(['missed/list']); ?>">
                                <span class="fas fa-phone"></span> Обзвон отсутствующих
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endif; ?>

            <?php if ($admin->can('viewSalary')): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['money/salary']); ?>">
                        <span class="fas fa-money-bill-wave"></span> Зарплата
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($admin->can('root')): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['company/index']); ?>">
                        <span class="fas fa-building"></span> Компании
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($admin->can('moneyManagement') || $admin->can('manageGiftCardTypes')): ?>
                <li class="nav-item dropdown" role="presentation">
                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                        Предоплаченные карты <span class="caret"></span>
                    </a>
                    <div class="dropdown-menu">
                        <?php if ($admin->can('moneyManagement')): ?>
                            <a class="dropdown-item" href="<?= Url::to(['gift-card/index']); ?>">
                                <span class="fas fa-file-invoice"></span> Список карт
                            </a>
                        <?php endif; ?>
                        <?php if ($admin->can('manageGiftCardTypes')): ?>
                            <a class="dropdown-item" href="<?= Url::to(['gift-card-type/index']); ?>">
                                <span class="fas fa-cog"></span> Типы карт
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endif; ?>

            <?php if ($admin->can('reportGroupMovement')
                || $admin->can('reportDebt')
                || $admin->can('reportMoney')
                || $admin->can('reportCash')
            ): ?>
                <li class="nav-item dropdown" role="presentation">
                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                        Отчёты <span class="caret"></span>
                    </a>
                    <div class="dropdown-menu">
                        <?php if ($admin->can('reportGroupMovement')): ?>
                            <a class="dropdown-item" href="<?= Url::to(['report/group-movement']); ?>">
                                <span class="fas fa-walking"></span> Отчет движения
                            </a>
                        <?php endif; ?>
                        <?php if ($admin->can('reportDebt')): ?>
                            <a class="dropdown-item" href="<?= Url::to(['report/debt']); ?>">
                                <span class="fas fa-info"></span> Отчет по должникам
                            </a>
                        <?php endif; ?>
                        <?php if ($admin->can('reportMoney')): ?>
                            <a class="dropdown-item" href="<?= Url::to(['report/money']); ?>">
                                <span class="fas fa-dollar-sign"></span> Финансовый отчёт
                            </a>
                        <?php endif; ?>
                        <?php if ($admin->can('reportCash')): ?>
                            <a class="dropdown-item" href="<?= Url::to(['report/cash']); ?>">
                                <span class="fas fa-coins"></span> Касса
                            </a>
                            <a class="dropdown-item" href="<?= Url::to(['report/rest-money']); ?>">
                                <span class="fas fa-funnel-dollar"></span> Остатки
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endif; ?>

            <?php if ($admin->can('content')): ?>
                <li class="nav-item dropdown" role="presentation">
                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                        Контент <span class="caret"></span>
                    </a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="<?= Url::to(['page/index']); ?>">
                            <span class="fas fa-file"></span> Страницы
                        </a>
                        <a class="dropdown-item" href="<?= Url::to(['menu/index']); ?>">
                            <span class="fas fa-bars"></span> Меню
                        </a>
                        <a class="dropdown-item" href="<?= Url::to(['widget-html/index']); ?>">
                            <span class="fas fa-cog"></span> Блоки
                        </a>
                        <div class="dropdown-divider" role="separator"></div>
                        <a class="dropdown-item" href="<?= Url::to(['high-school/index']); ?>">
                            <span class="fas fa-graduation-cap"></span> ВУЗы
                        </a>
                        <a class="dropdown-item" href="<?= Url::to(['lyceum/index']); ?>">
                            <span class="fas fa-landmark"></span> Лицеи
                        </a>
                        <div class="dropdown-divider" role="separator"></div>
                        <a class="dropdown-item" href="<?= Url::to(['promotion/index']); ?>">
                            <span class="fas fa-bell"></span> Акции
                        </a>
                        <a class="dropdown-item" href="<?= Url::to(['blog/index']); ?>">
                            <span class="far fa-newspaper"></span> Блог
                        </a>
                        <a class="dropdown-item" href="<?= Url::to(['news/index']); ?>">
                            <span class="far fa-newspaper"></span> Новости
                        </a>
                    </div>
                </li>

                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['quiz/index']); ?>">
                        <span class="fas fa-clipboard"></span> Тесты
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['quiz-result/index']); ?>">
                        <span class="fas fa-clipboard-list"></span> Результаты тестов
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($admin->can('manager')): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="<?= Url::to(['bot-mailing/index']); ?>">
                        <span class="fas fa-mail-bulk"></span> Рассылки 
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
