<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $highSchools \common\models\HighSchool[] */
/* @var $webpage \common\models\Webpage|null */
/* @var $prefix string */
/* @var $title string */

$this->title = 'Настройки страницы "' . $title . '"';
$this->params['breadcrumbs'][] = ['label' => $title, 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Настройки страницы';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(['options' => ['onSubmit' => 'return Main.submitSortableForm(this);']]); ?>

    <div class="row">
        <div class="col-xs-12">
            <?php
            $sortableItems = [];
            foreach ($highSchools as $highSchool) {
                $sortableItems[] = [
                    'content' => '<span class="glyphicon glyphicon-sort"></span> '. $highSchool->name,
                    'options' => ['id' => $prefix . $highSchool->id],
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

    <?= $this->render('/webpage/_form', [
        'form' => $form,
        'webpage' => $webpage,
    ]); ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
