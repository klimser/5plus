<?php

use yii\bootstrap4\Html;
use \yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $subjectCategory common\models\SubjectCategory */

$this->title = $subjectCategory->isNewRecord ? 'Новая группа курсов' : $subjectCategory->name;
$this->params['breadcrumbs'][] = ['label' => 'Группы курсов', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Изменить ' . $subjectCategory->name;
?>
<div class="page-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="page-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($subjectCategory, 'name')->textInput(['maxlength' => true]) ?>

        <?= $this->render('/webpage/_form', [
            'form' => $form,
            'webpage' => $subjectCategory->webpage,
            'module' => isset($module) ? $module : null,
        ]); ?>

        <div class="form-group">
            <?= Html::submitButton($subjectCategory->isNewRecord ? 'создать' : 'сохранить', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
