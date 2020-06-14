<?php

/* @var $this \yii\web\View */
/* @var $content string */

use common\models\User;
use yii\bootstrap4\Breadcrumbs;
use yii\helpers\Html;
use yii\bootstrap4\Nav;
use yii\bootstrap4\NavBar;
use yii\jui\JuiAsset;
use yii\web\YiiAsset;

use common\widgets\Alert;

\dosamigos\datepicker\DatePickerLanguageAsset::register($this);

$this->registerJs(<<<SCRIPT
    Main.initTooltip();
    Main.initPhoneFormatted();
SCRIPT
);

$this->beginPage();
YiiAsset::register($this);
JuiAsset::register($this);
$this->render('/grunt-assets');
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
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
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100 ">
    <?php $this->beginBody() ?>

    <?php if (!Yii::$app->user->isGuest): ?>
        <header>
            <?php
             NavBar::begin([
                'brandLabel' => '<img src="' . Yii::$app->homeUrl . 'images/logo.svg" style="max-height: 100%;"><span class="ml-3">5 с плюсом</span>',
                'brandUrl' => Yii::$app->homeUrl,
                'brandOptions' => ['style' => 'height: 40px;'],
                'containerOptions' => ['class' => ['justify-content-end']],
                 'options'=> ['class' => ['navbar', 'navbar-expand-md', 'navbar-light', 'bg-light', 'fixed-top'],],
            ]);
            $menuItems = [];
            if (Yii::$app->user->identity->role == User::ROLE_ROOT) {
                $menuItems[] = [
                    'label' => 'Пользователи',
                    'encode' => false,
                    'url' => ['user/index'],
                ];
                $menuItems[] = [
                    'label' => '<span class="fas fa-broom"></span>',
                    'encode' => false,
                    'url' => ['site/clear-cache'],
                ];
            }
            $menuItems[] = [
                'label' => Yii::$app->user->identity->name,
                'url' => ['user/update'],
            ];
            $menuItems[] = [
                'label' => '<span class="fas fa-sign-out-alt"></span>',
                'encode' => false,
                'url' => ['site/logout'],
            ];
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav'],
                'items' => $menuItems,
            ]);
            NavBar::end();
        ?>
        </header>
    <?php endif; ?>

    <main class="container <?php if (Yii::$app->user->isGuest): ?>pt-0 my-auto<?php endif; ?>" role="main">
        <?php if (!Yii::$app->user->isGuest): ?>
            <nav class="d-print-none" role="navigation">
                <?=  Breadcrumbs::widget(['links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : []]); ?>
            </nav>
        <?php endif; ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </main>

    <footer class="footer mt-auto border-top">
        <div class="container">
            <div class="row justify-content-start align-content-center">
                <div class="col">
                    <p class="my-3">&copy; Exclusive education <?= date('Y') ?></p>
                </div>
            </div>
        </div>
    </footer>

    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
