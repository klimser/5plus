<?php

use common\components\WidgetHtml;
use yii\bootstrap4\Html;
use yii\web\YiiAsset;

/* @var $this \yii\web\View */
/* @var $content string */

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

    <?php
//     $this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/pagePiling.js/1.5.6/jquery.pagepiling.min.css', ['integrity' => 'sha512-W9OWS8dgpQBw5Hb+tbMto1BMsHvYOXT/AFBGvASCPUJleaEdpOAN5lzgj9RrTbo3YrlR+m9xdOBccp8F+SFyQg==', 'crossorigin' => 'anonymous', 'depends' => [\yii\bootstrap4\BootstrapAsset::class]]);
    
     $this->registerCssFile(Yii::$app->homeUrl . 'css/landing.css?v=' . time(), ['depends' => [\yii\bootstrap4\BootstrapAsset::class]]);
     ?>

    <?= Html::csrfMetaTags() ?>
    <title>Онлайн-школа "Пять с плюсом"</title>
    <?php $this->head() ?>
    <?= YII_ENV == 'prod' ? WidgetHtml::getByName('google_analytics') : ''; ?>
    <?= YII_ENV == 'prod' ? WidgetHtml::getByName('facebook_pixel') : ''; ?>
</head>
<body>
<?php $this->beginBody() ?>

<?= $content ?>

<?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>

