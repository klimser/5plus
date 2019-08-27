<?php

use backend\components\DefaultValuesComponent;
use common\components\bitrix\Bitrix;
use dosamigos\tinymce\TinyMce;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $subject common\models\Subject */
/* @var $subjectCategories \common\models\SubjectCategory[] */

$this->title = $subject->isNewRecord ? 'Новый курс' : $subject->name;
$this->params['breadcrumbs'][] = ['label' => 'Курсы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $subject->name;

?>
<div class="subject-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="subject-form">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <?= $form->field($subject, 'name')->textInput(['required' => true, 'maxlength' => true]); ?>

        <?= $form->field($subject, 'category_id')->dropDownList(ArrayHelper::map($subjectCategories, 'id', 'name')); ?>

        <?= $form->field($subject, 'bitrix_id')->dropDownList(Bitrix::SUBJECT_LIST, ['required' => true]); ?>

        <?=
        $form->field($subject, 'imageFile', ['options' => ['class' => 'form-group col-xs-10']])
            ->fileInput(['required' => $subject->isNewRecord, 'accept' => 'image/jpeg,image/png', 'data' => ['id' => $subject->id]]);
        ?>
        <div class="col-xs-2">
            <?php if ($subject->image): ?>
                <img alt="<?= $subject->name; ?>" src="<?= $subject->imageUrl; ?>" style="max-width: 100%;">
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>

        <?= $form->field($subject, 'description')->textarea() ?>

        <?=
        $form->field($subject, 'content')->widget(TinyMce::class, DefaultValuesComponent::getTinyMceSettings());
        ?>

        <?= $this->render('/webpage/_form', [
            'form' => $form,
            'webpage' => $subject->webpage,
            'module' => isset($module) ? $module : null,
        ]); ?>

        <div class="form-group">
            <?= Html::submitButton($subject->isNewRecord ? 'создать' : 'сохранить', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

</div>
