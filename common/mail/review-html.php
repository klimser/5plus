<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $userName string */

?>
<p>Здравствуйте!</p>

<p>На сайте 5plus.uz посетитель <?= $userName; ?> оставил отзыв, проверьте его содержание и утвердите или отклоните его.</p>

<p><?= Html::a('Обработать отзыв', 'http://cabinet.5plus.uz/review/index') ?></p>
