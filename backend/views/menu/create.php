<?php

use yii\bootstrap4\Html;


/* @var $this yii\web\View */
/* @var $menu common\models\Menu */

$this->title = 'Создать меню';
$this->params['breadcrumbs'][] = ['label' => 'Меню', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="menu-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'menu' => $menu,
    ]) ?>

</div>
