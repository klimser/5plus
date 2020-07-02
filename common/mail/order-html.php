<?php
use yii\bootstrap4\Html;

/* @var $this yii\web\View */
/* @var $userName string */
/* @var $subjectName string */

?>
<p>Здравствуйте!</p>

<p>На сайте 5plus.uz посетитель <?= $userName; ?> оставил заявку на занятие "<?= $subjectName; ?>".</p>

<p><?= Html::a('Обработать заявку', 'http://cabinet.5plus.uz/order/index') ?></p>
