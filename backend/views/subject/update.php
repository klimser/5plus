<?php

use common\components\DefaultValuesComponent;
use common\components\bitrix\Bitrix;
use dosamigos\tinymce\TinyMce;
use yii\helpers\ArrayHelper;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $subject common\models\Subject */
/* @var $subjectCategories \common\models\SubjectCategory[] */

$this->title = $subject->isNewRecord ? 'Новый курс' : $subject->name;
$this->params['breadcrumbs'][] = ['label' => 'Курсы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $subject->name;

$bitrixSubjects = array_unique(array_merge(array_keys(Bitrix::DEAL_SUBJECT_LIST), array_keys(Bitrix::USER_SUBJECT_LIST)));
sort($bitrixSubjects);

?>
<div class="subject-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="subject-form">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <?= $form->field($subject, 'name')->textInput(['required' => true, 'maxlength' => true]); ?>

        <?= $form->field($subject, 'category_id')->dropDownList(ArrayHelper::map($subjectCategories, 'id', 'name')); ?>

        <?= $form->field($subject, 'bitrix_name')->dropDownList(ArrayHelper::map($bitrixSubjects, function($val) { return $val; }, function($val) { return $val; }), ['required' => true]); ?>

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
