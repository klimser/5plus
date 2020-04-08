<?php

/* @var $this \yii\web\View */
/* @var $content string */

use common\models\Menu;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;
use common\components\WidgetHtml;

/* @var $webpage \common\models\Webpage */
$webpage = array_key_exists('webpage', $this->params) ? $this->params['webpage'] : null;

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
    <div class="content-block">
        <div class="mobile-header">
            <div class="btn-menu-open">
                <span class="line1"></span>
                <span class="line2"></span>
                <span class="line3"></span>
            </div>
        </div>
        <div class="header-box container">
            <div class="logo-block">
                <a href="<?= Yii::$app->homeUrl; ?>">
                    <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/logo.svg" alt="Учебный центр &quot;Пять с плюсом&quot;" title="Учебный центр &quot;Пять с плюсом&quot;">
                </a>
                <a href="<?= Yii::$app->homeUrl; ?>" class="logo">
                    <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/logo.svg" alt="site logo">
                </a>
                <div class="name">
                    Пять с плюсом
                    <span class="name-small">ВАШ УЧЕБНЫЙ ЦЕНТР</span>
                </div>
            </div>
            <div class="contacts-block">
                <div class="address">
                    <img class="ico" src="images/address-ico.png" alt="ico">
                    <div class="text">
                        <div class="title">Адрес:</div>
                        <address class="desc">г.Ташкент,<br> ул. Ойбек, 16</address>
                    </div>
                </div>
                <div class="phone">
                    <img src="images/mobile-ico.png" alt="ico" class="ico">
                    <div class="text">
                        <div class="title">Телефон:</div>
                        <div class="desc">
                            <a class="tel" href="tel:+998712000350"><span class="code">+998-71</span> 200-03-50</a>
                        </div>
                    </div>
                </div>
                <div class="social">
                    <div class="title">Мы в соц сетях:</div>
                    <ul class="social-list">
                        <li class="item"><a class="bgc-ins" href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram"></i></a></li>
                        <li class="item"><a class="bgc-fb" href="https://www.facebook.com/" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
                        <li class="item"><a class="bgc-tg" href="https://telegram.org/" target="_blank"><i class="fab fa-telegram-plane"></i></a></li>
                    </ul>
                </div>
            </div>

            <nav class="main-menu-block">
                <ul class="main-menu-list">
                    <li class="item"><a href="#">НОВОСТИ</a></li>
                    <li class="item"><a href="#">КОМАНДА</a></li>
                    <li class="item"><a href="#">ЦЕНЫ</a></li>
                    <li class="item"><a href="#">РЕЗУЛЬТАТЫ</a></li>
                    <li class="item"><a href="#">ОТЗЫВЫ</a></li>
                    <li class="item"><a href="#">ЭТО ПОЛЕЗНО</a></li>
                    <li class="item"><a href="#">ВУЗЫ ТАШКЕНТА</a></li>
                    <li class="item"><a href="#">КОНТАКТЫ</a></li>
                </ul>
            </nav>
        </div>
        <div class="container">
            <div class="row">
                <div class="logo-block col-xs-5 col-sm-3 col-lg-2 text-center">
                    <a href="<?= Yii::$app->homeUrl; ?>">
                        <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/logo.svg" alt="Учебный центр &quot;Пять с плюсом&quot;" title="Учебный центр &quot;Пять с плюсом&quot;">
                    </a>
                </div>
                <div class="phone-block col-xs-7 col-sm-3 col-md-5 col-lg-5">
                    <?= WidgetHtml::getByName('phones'); ?>
                </div>
                <div class="email-block col-xs-7 col-sm-3 col-md-2 col-lg-2">
                    <?= WidgetHtml::getByName('email'); ?>
                </div>
                <div class="address-block col-xs-7 col-sm-3 col-md-2 col-lg-3">
                    <?= WidgetHtml::getByName('address'); ?>
                </div>
                <div class="col-xs-12 col-sm-9 col-lg-10">
                    <?php NavBar::begin(['options' => ['class' => 'navbar-default'], 'innerContainerOptions' => ['class' => 'container-fluid']]); ?>
                    <?= Nav::widget([
                        'options' => ['class' => 'navbar-nav'],
                        'items' => Menu::getMenuItemsCached(Menu::MAIN_MENU_ID, $webpage),
                    ]); ?>
                    <?php NavBar::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="clouds-line-top"></div>
</header>

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

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
