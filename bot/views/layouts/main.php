<?php

/* @var $this \yii\web\View */
/* @var $content string */

use common\models\Menu;
use common\widgets\Alert;
use yii\bootstrap4\Html;
use yii\bootstrap4\Nav;
use common\components\WidgetHtml;
use yii\helpers\Url;

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="creator" content="Sergey Klimov <https://sergey-klimov.ru>">

    <title>Service</title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
