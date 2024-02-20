<?php

/* @var $this \yii\web\View */
/* @var $content string */

use common\models\Menu;
use common\widgets\Alert;
use yii\bootstrap4\Html;
use yii\bootstrap4\Nav;
use common\components\WidgetHtml;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $webpage \common\models\Webpage */
$webpage = array_key_exists('webpage', $this->params) ? $this->params['webpage'] : null;

/* @var $quizWebpage \common\models\Webpage */
$quizWebpage = array_key_exists('quizWebpage', $this->params) ? $this->params['quizWebpage'] : null;

/* @var $paymentWebpage \common\models\Webpage */
$paymentWebpage = array_key_exists('paymentWebpage', $this->params) ? $this->params['paymentWebpage'] : null;

$this->beginPage();
YiiAsset::register($this);
$this->render('/grunt-assets');
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-config" content="<?= Yii::$app->homeUrl; ?>icons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <meta name="creator" content="Sergey Klimov <https://sergey-klimov.ru>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Yii::$app->homeUrl; ?>icons/apple-touch-icon.png?v=fjhbdf9b40">
    <link rel="icon" type="image/png" href="<?= Yii::$app->homeUrl; ?>icons/favicon-32x32.png?v=fjhbdf9b40" sizes="32x32">
    <link rel="icon" type="image/png" href="<?= Yii::$app->homeUrl; ?>icons/favicon-16x16.png?v=fjhbdf9b40" sizes="16x16">
    <link rel="manifest" href="<?= Yii::$app->homeUrl; ?>site.webmanifest?v=fjhbdf9b40">
    <link rel="mask-icon" href="<?= Yii::$app->homeUrl; ?>safari-pinned-tab.svg?v=fjhbdf9b40" color="#65a2d9">
    <link rel="shortcut icon" href="<?= Yii::$app->homeUrl; ?>favicon.ico?v=fjhbdf9b40">

    <?= $this->render('/preload'); ?>
    <?php if (YII_ENV == 'prod'): ?>
        <?= $this->render('/preconnect'); ?>
    <?php endif; ?>
    
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title); ?></title>
    <?php $this->head() ?>
    <?= YII_ENV == 'prod' ? WidgetHtml::getByName('google_analytics') : ''; ?>
    <?= YII_ENV == 'prod' ? WidgetHtml::getByName('facebook_pixel') : ''; ?>
</head>
<body class="<?= $webpage && $webpage->main ? ' front-page ' : ''; ?>">
<?php $this->beginBody() ?>
<?= YII_ENV == 'prod' ? WidgetHtml::getByName('yandex_metrika') : ''; ?>

<header class="header w-100">
    <div class="header-box">
        <div class="bg-light d-lg-none w-100 navbar-bg"></div>
        <div class="container navbar-body">
            <div class="row no-gutters align-items-center justify-content-between align-items-lg-start justify-content-lg-center pt-lg-4">
                <button class="btn-menu-open btn btn-light col-auto d-lg-none px-2" type="button" onclick="Main.toggleMainMenu(this);" data-target=".main-menu-collapsible" aria-label="Главное меню">
                    <span class="line1"></span>
                    <span class="line2"></span>
                    <span class="line3"></span>
                </button>
                <div class="logo-block col-6 ml-1 ml-sm-2 ml-lg-0 order-lg-1 d-flex">
                    <a href="<?= Yii::$app->homeUrl; ?>" class="d-flex">
                        <div class="row no-gutters align-items-center">
                            <div class="logo col-3 col-sm-2 col-lg-3 pr-1 pr-lg-0">
                                <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/logo.svg" alt="Учебный центр &quot;Пять с плюсом&quot;" title="Учебный центр &quot;Пять с плюсом&quot;">
                            </div>
                            <div class="name col-9 col-sm-10 col-lg-9 pl-lg-3">
                                Пять с плюсом
                                <div class="name-small text-uppercase">Ваш учебный центр</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="social col-auto col-lg-2 order-lg-4 d-flex flex-lg-wrap">
                    <div class="title d-none d-lg-flex w-100">Мы в соц сетях:</div>
                    <?= WidgetHtml::getByName('social'); ?>
                </div>
                
                <nav class="col-10 col-sm-6 pb-3 ml-n3 pt-1 col-lg-auto ml-lg-0 mt-lg-4 order-lg-10 main-menu-block collapse main-menu-collapsible d-lg-flex">
                    <?= Nav::widget([
                        'options' => ['class' => 'main-menu-list'],
                        'items' => Menu::getMenuItemsCached(Menu::MAIN_MENU_ID, $webpage),
                    ]); ?>
                </nav>
                <div class="w-100 d-none d-sm-block d-lg-none"></div>
                <div class="address col-10 col-sm-6 pb-4 ml-n3 ml-lg-0 col-lg-2 order-lg-2 collapse main-menu-collapsible d-lg-flex flex-lg-wrap">
                    <?= WidgetHtml::getByName('address'); ?>
                </div>
                <div class="w-100 d-none d-sm-block d-lg-none"></div>
                <div class="phone col-10 col-sm-6 pb-3 ml-n3 ml-lg-0 col-lg-2 order-lg-3 collapse main-menu-collapsible d-lg-flex flex-lg-wrap">
                    <?= WidgetHtml::getByName('phones'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php if ($webpage && $webpage->main): ?>
        <div class="main-slider-box container">
            <div class="main-slider">
                <div class="swiper-main-slider swiper-container">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="text">
                                <h2 class="title">Мы дорожим качеством ваших знаний</h2>
                                <div class="btns-group flex-column align-items-center align-items-md-start">
                                    <a href="<?= Url::to(['webpage', 'id' => $quizWebpage->id]); ?>" class="btn btn-danger btn-ico btn-lg p-3 p-lg-4">Проверь свои знания <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/puzzle-ico.png" alt="ico" class="ico"></a>
                                    <div class="btn btn-primary btn-ico btn-lg p-3 p-lg-4" data-container="body" data-toggle="popover" data-placement="bottom" data-trigger="click" data-html="true" data-content="<a class='btn btn-primary' href='<?= Url::to(['webpage', 'id' => $paymentWebpage->id, 'type' => 'student']); ?>'>для учащихся</a><br><br> <a class='btn btn-primary' href='<?= Url::to(['webpage', 'id' => $paymentWebpage->id, 'type' => 'new']); ?>'>для новых студентов</a>">
                                        Онлайн оплата <span class="far fa-credit-card ml-3"></span>
                                    </div>
                                    <a href="https://beshplus.uz" class="btn btn-danger btn-ico btn-lg p-3 p-lg-4" target="_blank">Онлайн-школа <span class="fas fa-globe ml-3"></span></a>
                                    <?php /* <a href="#" class="btn btn-outline-info btn-ico btn-lg">Мы вам перезвоним <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/phone-ico.png" alt="ico" class="ico"></a> */ ?>
                                </div>
                            </div>
                            <div class="img">
                                <picture>
                                    <source srcset="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-img-1.webp" type="image/webp">
                                    <source srcset="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-img-1.jpg" type="image/jpeg">
                                    <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-img-1.jpg" alt="image">
                                </picture>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-button-prev"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
                <div class="swiper-button-next"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
            </div>
        </div>
    <?php else: ?>
        <?php if (array_key_exists('h1', $this->params) && $this->params['h1']): ?>
            <div class="page-title-box container">
                <h1 class="page-title"><?= $this->params['h1']; ?></h1>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</header>

<main class="content">
    <div class="container"><?= Alert::widget() ?></div>
    <?= $content ?>
</main>

<footer class="footer">
    <div class="container">
        <div class="row pt-lg-5 d-md-block">
            <div class="col-12 col-md-6 col-lg-7 float-md-left">
                <div class="row align-items-center my-3">
                    <div class="col-3 col-lg-2 pr-0 pr-lg-3">
                        <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/logo_inverse.svg" alt="Учебный центр &quot;Пять с плюсом&quot;" title="Учебный центр &quot;Пять с плюсом&quot;">
                    </div>
                    <div class="col-9">
                        <div class="name">
                            Пять с плюсом
                            <div class="name-small text-left">ВАШ УЧЕБНЫЙ ЦЕНТР</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-5 mt-3 float-md-right contacts-block">
                <h2 class="block-title">Контактная информация</h2>
                <div class="item mb-4">
                    <span class="ico icon-map"></span>
                    <div class="text">
                        <?= WidgetHtml::getByName('address'); ?>
                    </div>
                </div>
                <div class="item landmark mb-4">
                    <span class="ico icon-map"></span>
                    <div class="text">
                        <?= WidgetHtml::getByName('landmark'); ?>
                    </div>
                </div>
                <div class="item phone mb-4">
                    <span class="ico icon-phone"></span>
                    <div class="text">
                        <?= WidgetHtml::getByName('phones'); ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-7 float-md-left mb-3 mt-md-3">
                <div id="map" class="map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1812.0439450559677!2d69.27450349091579!3d41.29657315438762!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38ae8b288a28d0f1%3A0xad929af041a2d7f5!2z0KPRh9C10LHQvdGL0Lkg0YbQtdC90YLRgCDCqyA1KyDQn9GP0YLRjCDRgSDQv9C70Y7RgdC-0LzCuw!5e0!3m2!1sru!2sde!4v1602842656556!5m2!1sru!2sde" width="100%" height="303" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-5 mb-3 float-md-right contacts-block">
                <div class="social text-center">
                    <div class="title">Мы в соц сетях:</div>
                    <div class="row justify-content-center mt-2">
                        <div class="col-auto">
                            <?= WidgetHtml::getByName('social'); ?>
                        </div>
                    </div>
                    <?php /*<div class="title">Мы в соц сетях:</div>
                <ul class="social-list">
                    <li class="item"><a class="bgc-ins" href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram"></i></a></li>
                    <li class="item"><a class="bgc-fb" href="https://www.facebook.com/" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
                    <li class="item"><a class="bgc-tg" href="https://telegram.org/" target="_blank"><i class="fab fa-telegram-plane"></i></a></li>
                </ul> */ ?>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="row border-top">
            <div class="col">
                <ul class="docs_menu my-3 text-center">
                    <li>Все права защищены &copy; Exclusive Education</li>
                    <?php
                        $menuItems = Menu::getMenuItemsCached(Menu::DOCS_MENU_ID, $webpage);
                        foreach ($menuItems as $menuItem):
                    ?>
                            <li><a href="<?= $menuItem['url']; ?>"><?= $menuItem['label']; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
