<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $quiz common\models\Quiz */
/* @var $subjects \common\models\Subject[] */

$this->title = 'Создать тест';
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="menu-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'quiz' => $quiz,
        'subjects' => $subjects,
    ]) ?>

</div>
