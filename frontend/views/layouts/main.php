<?php

/* @var $this \yii\web\View */
/* @var $webpage \common\models\Webpage */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;
use common\components\WidgetHtml;

$this->beginPage();
$this->render('/grunt-assets');
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Yii::$app->homeUrl; ?>icons/apple-touch-icon.png?v=Nm5Ovj34NA">
    <link rel="icon" type="image/png" href="<?= Yii::$app->homeUrl; ?>icons/favicon-32x32.png?v=Nm5Ovj34NA" sizes="32x32">
    <link rel="icon" type="image/png" href="<?= Yii::$app->homeUrl; ?>icons/favicon-16x16.png?v=Nm5Ovj34NA" sizes="16x16">
    <link rel="manifest" href="<?= Yii::$app->homeUrl; ?>site.webmanifest?v=Nm5Ovj34NA">
    <link rel="mask-icon" href="<?= Yii::$app->homeUrl; ?>safari-pinned-tab.svg?v=Nm5Ovj34NA" color="#65a2d9">
    <link rel="shortcut icon" href="<?= Yii::$app->homeUrl; ?>favicon.ico?v=Nm5Ovj34NA">
    <meta name="msapplication-TileColor" content="#ffc40d">
    <meta name="msapplication-config" content="<?= Yii::$app->homeUrl; ?>icons/browserconfig.xml?v=Nm5Ovj34NA">
    <meta name="theme-color" content="#65a2d9">

    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title); ?></title>
    <?php $this->head() ?>
    <?= YII_ENV == 'prod' ? WidgetHtml::getByName('google_analytics') : ''; ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= YII_ENV == 'prod' ? WidgetHtml::getByName('yandex_metrika') : ''; ?>


<header>
    <div class="content-block">
        <div class="container">
            <div class="row">
                <div class="logo-block col-xs-5 col-sm-3 col-lg-2 text-center">
                    <a href="<?= Yii::$app->homeUrl; ?>">
                        <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/logo.svg" alt="Учебный центр &quot;Пять с плюсом&quot;" title="Учебный центр &quot;Пять с плюсом&quot;">
                    </a>
                </div>
                <div class="phone-block col-xs-7 col-sm-3 col-md-5 col-lg-6">
                    <?= WidgetHtml::getByName('phones'); ?>
                </div>
                <div class="email-block col-xs-7 col-sm-3 col-md-2 col-lg-2">
                    <?= WidgetHtml::getByName('email'); ?>
                </div>
                <div class="address-block col-xs-12 col-sm-3 col-md-2 col-lg-2">
                    <?= WidgetHtml::getByName('address'); ?>
                </div>
                <div class="col-xs-12 col-sm-9 col-lg-10">
                    <?php NavBar::begin(['options' => ['class' => 'navbar-default'], 'innerContainerOptions' => ['class' => 'container-fluid']]); ?>
                    <?= Nav::widget([
                        'options' => ['class' => 'navbar-nav'],
                        'items' => \common\models\Menu::getMenuItemsCached(\common\models\Menu::MAIN_MENU_ID, array_key_exists('webpage', $this->params) ? $this->params['webpage'] : null),
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
</div>

<footer class="footer">
    <div class="clouds-line-bottom"></div>
    <div class="content-block">
        <div class="container">
            <div class="row">
                <div class="col-xs-5 col-sm-3 col-md-2 company_name">
                    <div>&copy; НОУ "Exclusive Education", <?= date('Y'); ?></div>
                    <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/footer_dots.svg">
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
                        Дизайн <a href="http://korden.uz"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/korden_logo.png"></a>
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