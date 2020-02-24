<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;

$this->registerJs(<<<SCRIPT
    Main.initTooltip();
    Main.initPhoneFormatted();
SCRIPT
);

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
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    if (!Yii::$app->user->isGuest) {
        NavBar::begin([
            'brandLabel' => '<div class="form-inline"><img src="' . \Yii::$app->homeUrl . 'images/logo.png" width="20" height="20"> <span style="margin-left: 10px;">5 с плюсом</span></div>',
            'brandUrl' => Yii::$app->homeUrl,
            'options' => [
                'class' => 'navbar-default',
            ],
        ]);
        $menuItems = [];
        if (\Yii::$app->user->identity->role == \common\models\User::ROLE_ROOT) {
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
            'options' => ['class' => 'navbar-nav navbar-right'],
            'items' => $menuItems,
        ]);
        NavBar::end();
    }
    ?>

    <div class="container">
        <?php if (!Yii::$app->user->isGuest): ?>
            <nav class="hidden-print" role="navigation">
                <?=  Breadcrumbs::widget(['links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : []]); ?>
            </nav>
        <?php endif; ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <p>&copy; Exclusive education <?= date('Y') ?></p>
            </div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
