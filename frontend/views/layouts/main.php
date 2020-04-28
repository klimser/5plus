<?php

/* @var $this \yii\web\View */
/* @var $content string */

use common\models\Menu;
use yii\helpers\Html;
use yii\bootstrap4\Nav;
use common\components\WidgetHtml;
use yii\helpers\Url;

/* @var $webpage \common\models\Webpage */
$webpage = array_key_exists('webpage', $this->params) ? $this->params['webpage'] : null;

/* @var $quizWebpage \common\models\Webpage */
$quizWebpage = array_key_exists('quizWebpage', $this->params) ? $this->params['quizWebpage'] : null;

/* @var $paymentWebpage \common\models\Webpage */
$paymentWebpage = array_key_exists('paymentWebpage', $this->params) ? $this->params['paymentWebpage'] : null;

$this->beginPage();
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
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Yii::$app->homeUrl; ?>icons/apple-touch-icon.png?v=fjhbdf9b40">
    <link rel="icon" type="image/png" href="<?= Yii::$app->homeUrl; ?>icons/favicon-32x32.png?v=fjhbdf9b40" sizes="32x32">
    <link rel="icon" type="image/png" href="<?= Yii::$app->homeUrl; ?>icons/favicon-16x16.png?v=fjhbdf9b40" sizes="16x16">
    <link rel="manifest" href="<?= Yii::$app->homeUrl; ?>site.webmanifest?v=fjhbdf9b40">
    <link rel="mask-icon" href="<?= Yii::$app->homeUrl; ?>safari-pinned-tab.svg?v=fjhbdf9b40" color="#65a2d9">
    <link rel="shortcut icon" href="<?= Yii::$app->homeUrl; ?>favicon.ico?v=fjhbdf9b40">

    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title); ?></title>
    <?php $this->head() ?>
    <?= YII_ENV == 'prod' ? WidgetHtml::getByName('google_analytics') : ''; ?>
    <?= YII_ENV == 'prod' ? WidgetHtml::getByName('facebook_pixel') : ''; ?>
</head>
<body class="<?= $webpage && $webpage->main ? ' front-page ' : ''; ?>">
<?php $this->beginBody() ?>
<?= YII_ENV == 'prod' ? WidgetHtml::getByName('yandex_metrika') : ''; ?>

<header class="header">
    <div class="mobile-header">
        <div class="btn-menu-open">
            <span class="line1"></span>
            <span class="line2"></span>
            <span class="line3"></span>
        </div>
    </div>
    <div class="header-box container">
        <div class="logo-block">
            <a href="<?= Yii::$app->homeUrl; ?>" class="d-flex">
                <div class="logo">
                    <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/logo.svg" alt="Учебный центр &quot;Пять с плюсом&quot;" title="Учебный центр &quot;Пять с плюсом&quot;">
                </div>
                <div class="name">
                    Пять с плюсом
                    <div class="name-small">ВАШ УЧЕБНЫЙ ЦЕНТР</div>
                </div>
            </a>
        </div>
        <div class="contacts-block">
            <div class="address">
                <span class="ico"></span>
                <div class="text">
                    <?= WidgetHtml::getByName('address'); ?>
                </div>
            </div>
            <div class="phone">
                <span class="ico"></span>
                <div class="text">
                    <?= WidgetHtml::getByName('phones'); ?>
                </div>
            </div>
            <div class="social">
                <?= WidgetHtml::getByName('social'); ?>
            </div>
        </div>

        <nav class="main-menu-block">
            <?= Nav::widget([
                'options' => ['class' => 'main-menu-list'],
                'items' => Menu::getMenuItemsCached(Menu::MAIN_MENU_ID, $webpage),
            ]); ?>
        </nav>
    </div>
    <?php if ($webpage && $webpage->main): ?>
        <div class="main-slider-box container">
            <div class="main-slider">
                <div class="swiper-main-slider swiper-container">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="text">
                                <h2 class="title">Мы дорожим качеством ваших знаний</h2>
                                <?php /*
                                <div class="desc">We are a technology company that understands <br>complexity of businesses, and with our technical <br>expertise, we help them transform and scale</div>
                                */ ?>
                                <div class="btns-group flex-column align-items-center align-items-md-start">
                                    <a href="<?= Url::to(['webpage', 'id' => $quizWebpage->id]); ?>" class="btn btn-danger btn-ico btn-lg">Проверь свои знания <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/puzzle-ico.png" alt="ico" class="ico"></a>
                                    <siv class="btn btn-primary btn-ico btn-lg" data-container="body" data-toggle="popover" data-placement="bottom" data-trigger="click" data-html="true" data-content="<a class='btn btn-primary' href='<?= Url::to(['webpage', 'id' => $paymentWebpage->id, 'type' => 'pupil']); ?>'>для учащихся</a><br><br> <a class='btn btn-primary' href='<?= Url::to(['webpage', 'id' => $paymentWebpage->id, 'type' => 'new']); ?>'>для новых студентов</a>">
                                        Онлайн оплата <span class="far fa-credit-card ml-3"></span>
                                    </siv>
                                    <?php /* <a href="#" class="btn btn-outline-info btn-ico btn-lg">Мы вам перезвоним <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/phone-ico.png" alt="ico" class="ico"></a> */ ?>
                                </div>
                            </div>
                            <div class="img"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-img.jpg" alt="image"></div>
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
    <?= $content ?>
</main>
<?php /*
<div class="container main-content">
    <div class="row">
        <?= Alert::widget(); ?>

        <?php if (array_key_exists('h1', $this->params) && $this->params['h1']): ?>
            <div class="col-xs-12">
                <h1><?= $this->params['h1']; ?></h1>
            </div>
        <?php endif; ?>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <?= Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                'homeLink' => ['url' => Yii::$app->homeUrl, 'label' => '<span class="glyphicon glyphicon-home"></span>', 'encode' => false],
                'options' => ['class' => 'breadcrumb', 'role' => 'navigation', 'aria-label' => 'breadcrumbs'],
            ]) ?>
        </div>
    </div>
    <?= $content ?>
    <?php if (!array_key_exists('hide_social', $this->params) || !$this->params['hide_social']): ?>
        <div class="row">
            <div class="col-xs-12">
                <?= YII_ENV == 'prod' ? WidgetHtml::getByName('social_share') : ''; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php // <?= YII_ENV == 'prod' ? WidgetHtml::getByName('fb_chat_plugin') : ''; ?>

<footer class="footer">
    <div class="clouds-line-bottom"></div>
    <div class="content-block">
        <div class="container">
            <div class="row">
                <div class="col-xs-5 col-sm-3 col-md-2 company_name">
                    <div>&copy; НОУ "Exclusive Education", <?= date('Y'); ?></div>
                    <img alt="" src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/footer_dots.svg">
                </div>
                <div class="phone-block col-xs-7 col-sm-3 col-md-2 col-md-offset-1">
                    <?= WidgetHtml::getByName('phones'); ?>
                </div>
                <div class="email-block col-xs-6 col-sm-3 col-md-2">
                    <?= WidgetHtml::getByName('email'); ?>
                </div>
                <div class="address-block col-xs-12 col-sm-3 col-md-2">
                    <?= WidgetHtml::getByName('address'); ?>
                </div>
                <div class="visible-sm clearfix"></div>
                <div class="social-block col-xs-12 col-md-3">
                    <div class="social-links text-center">
                        <?= WidgetHtml::getByName('social'); ?>
                    </div>
                    <div>
                        Сайт сделал <a href="https://sergey-klimov.ru"><b>Сергей Климов</b></a>
                    </div>
                    <div>
                        Дизайн <a href="http://korden.uz"><img alt="korden" src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/korden_logo.png"></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
*/ ?>

<footer class="footer">
    <div class="footer-box container">
        <div class="logo-n-map-block">
            <div class="logo row justify-content-start">
                <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/logo_inverse.svg" alt="Учебный центр &quot;Пять с плюсом&quot;" title="Учебный центр &quot;Пять с плюсом&quot;">
                <div class="name">
                    Пять с плюсом
                    <div class="name-small text-left">ВАШ УЧЕБНЫЙ ЦЕНТР</div>
                </div>
            </div>
            <div id="map" class="map"></div>
        </div>
        <div class="contacts-block">
            <h2 class="block-title">Контактная информация</h2>
            <div class="item">
                <span class="ico icon-map"></span>
                <div class="text">
                    <?= WidgetHtml::getByName('address'); ?>
                </div>
            </div>
            <div class="item landmark">
                <span class="ico icon-map"></span>
                <div class="text">
                    <?= WidgetHtml::getByName('landmark'); ?>
                </div>
            </div>
            <div class="item phone">
                <span class="ico icon-phone"></span>
                <div class="text">
                    <?= WidgetHtml::getByName('phones'); ?>
                </div>
            </div>
            <div class="social">
                <?= WidgetHtml::getByName('social'); ?>
                <?php /*<div class="title">Мы в соц сетях:</div>
                <ul class="social-list">
                    <li class="item"><a class="bgc-ins" href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram"></i></a></li>
                    <li class="item"><a class="bgc-fb" href="https://www.facebook.com/" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
                    <li class="item"><a class="bgc-tg" href="https://telegram.org/" target="_blank"><i class="fab fa-telegram-plane"></i></a></li>
                </ul> */ ?>
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=AIzaSyA75jBu3AhiE4wgujH4Exgnj8L0ILWxYVo&callback=initMap" async defer></script>
</body>
</html>
<?php $this->endPage() ?>
