<?php

use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use yii\jui\Sortable;

/* @var $this yii\web\View */
/* @var $webpage \common\models\Webpage|null */
/* @var $teachers \common\models\Teacher[] */
/* @var $prefix string */

$this->title = 'Настройки страницы "Учителя"';
$this->params['breadcrumbs'][] = ['label' => 'Учителя', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Настройки страницы';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(['options' => ['onSubmit' => 'return Main.submitSortableForm(this);']]); ?>

    <div class="form-group">
        <?php
        $sortableItems = [];
        foreach ($teachers as $teacher) {
            $sortableItems[] = [
                'content' => '<span class="fas fa-arrows-alt"></span> '. $teacher->name,
                'options' => ['id' => $prefix . $teacher->id],
            ];
        }
        ?>
        <?= Sortable::widget([
            'items' => $sortableItems,
            'options' => ['tag' => 'ol'],
            'itemOptions' => ['tag' => 'li', 'class' => 'border px-2 py-1 my-1'],
            'clientOptions' => ['cursor' => 'move'],
        ]); ?>
    </div>
    <hr>

    <?= $this->render('/webpage/_form', [
        'form' => $form,
        'webpage' => $webpage,
    ]); ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end(); ?>
