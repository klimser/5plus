<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $categories \common\models\SubjectCategory[] */
/* @var $prefix string */

$this->title = 'Порядок отображения групп курсов';
$this->params['breadcrumbs'][] = ['label' => 'Группы курсов', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Порядок отображения';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(['options' => ['onSubmit' => 'return Main.submitSortableForm(this);']]); ?>

    <div class="row">
        <div class="col-xs-12">
            <?php
            $sortableItems = [];
            foreach ($categories as $category) {
                $sortableItems[] = [
                    'content' => '<span class="glyphicon glyphicon-sort"></span> '. $category->name,
                    'options' => ['id' => $prefix . $category->id],
                ];
            }
            ?>
            <?= \yii\jui\Sortable::widget([
                'items' => $sortableItems,
                'options' => ['tag' => 'ol'],
                'itemOptions' => ['tag' => 'li'],
                'clientOptions' => ['cursor' => 'move'],
            ]); ?>
        </div>
    </div>
    <hr>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>

<?php ActiveForm::end(); ?>
