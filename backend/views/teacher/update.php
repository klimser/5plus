<?php

use common\components\DefaultValuesComponent;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

/* @var $this yii\web\View */
/* @var $teacher common\models\Teacher */

$this->title = $teacher->isNewRecord ? 'Новый учитель' : $teacher->name;
$this->params['breadcrumbs'][] = ['label' => 'Учителя', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="teacher-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php
    $script = '';
    foreach ($teacher->subjects as $subject) {
        $script .= 'Teacher.activeSubjectIds.push(' . $subject->id . ');' . "\n";
    }
    $script .= 'Teacher.init();' . "\n";
    $this->registerJs($script);

    ?>

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data', 'class' => 'row']]); ?>

    <?= $form->field($teacher, 'name', ['options' => ['class' => 'form-group col-12']])->textInput(['maxlength' => true]) ?>

    <?= $form->field($teacher, 'phone', ['options' => ['class' => 'form-group col-12 col-lg-6']])->textInput(['maxlength' => true, 'pattern' => '\+\d{12}']) ?>

    <?= $form->field($teacher, 'birthday', ['options' => ['class' => 'form-group col-12 col-lg-6']])->widget(DatePicker::class,
        ArrayHelper::merge(
            DefaultValuesComponent::getDatePickerSettings(),
            [
                'value' => $teacher->birthday ? $teacher->birthdayDate->format('d.m') : null,
                'dateFormat' => 'dd.MM',
                'options' => [
                    'pattern' => '\d{2}.\d{2}',
                ],
            ]));?>

    <?= $form->field($teacher, 'contractNumber', ['options' => ['class' => 'form-group col-12 col-lg-6']]); ?>
    
    <?= $form->field($teacher, 'contractDate', ['options' => ['class' => 'form-group col-12 col-lg-6']])->widget(DatePicker::class,
        ArrayHelper::merge(
            DefaultValuesComponent::getDatePickerSettings(),
            [
                'value' => $teacher->contractDate ? $teacher->contractDate->format('d.m.Y') : null,
            ]));
    ?>
    
    <?= $form->field($teacher, 'title', ['options' => ['class' => 'form-group col-12 col-lg-6']])->textInput(['maxlength' => true, 'placeholder' => 'Например: Ваш учитель химии']) ?>
    <?= $form->field($teacher, 'page_visibility', ['options' => ['class' => 'form-group col-12 col-lg-6']])->checkbox() ?>

    <?= $form->field($teacher, 'descriptionForEdit', ['options' => ['class' => 'form-group col-12']])->hint('Чтобы выделить часть текста цветом, заключите текст в {{текст}}')->textarea(['rows' => 6]) ?>

    <div class="row">
        <?=
        $form->field($teacher, 'photoFile', ['options' => ['class' => 'form-group col-10']])
            ->fileInput(['accept' => 'image/jpeg,image/png', 'data' => ['id' => $teacher->id]]);
        ?>
        <div class="col-2">
            <?php if ($teacher->photo): ?>
                <img class="img-fluid" src="<?= $teacher->imageUrl; ?>">
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12">
        <b>Предметы:</b><br>
        <div id="teacher_subjects" class="container-fluid">
            <?php foreach ($teacher->subjects as $subject): ?>
                <div class="row form-group">
                    <div class="col-10 col-md-11">
                        <input type="hidden" name="subject[]" value="<?= $subject->id; ?>">
                        <input readonly class="form-control-plaintext" value="<?= $subject->name; ?>">
                    </div>
                    <div class="col-2 col-md-1">
                        <button class="btn btn-outline-dark" onclick="return Teacher.removeSubject(this);"><span class="fas fa-times"></span></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-outline-dark btn-sm" onclick="return Teacher.renderSubjectForm();"><span class="fas fa-plus"></span> Добавить предмет</button>
        <hr>

        <?= $this->render('/webpage/_form', [
            'form' => $form,
            'webpage' => $teacher->webpage,
            'module' => isset($module) ? $module : null,
        ]); ?>
    </div>

    <div class="form-group col-12">
        <?= Html::submitButton($teacher->isNewRecord ? 'добавить' : 'сохранить', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
