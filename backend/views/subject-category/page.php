<?php

use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use yii\jui\Sortable;

/* @var $this yii\web\View */
/* @var $categories \common\models\SubjectCategory[] */
/* @var $prefix string */

$this->title = 'Порядок отображения групп курсов';
$this->params['breadcrumbs'][] = ['label' => 'Группы курсов', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Порядок отображения';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(['options' => ['onSubmit' => 'return Main.submitSortableForm(this);']]); ?>

    <div class="form-group">
        <?php
        $sortableItems = [];
        foreach ($categories as $category) {
            $sortableItems[] = [
                'content' => '<span class="fas fa-arrows-alt"></span> '. $category->name,
                'options' => ['id' => $prefix . $category->id],
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

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end(); ?>
