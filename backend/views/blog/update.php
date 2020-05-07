<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $blog common\models\Promotion */

$this->title = $blog->isNewRecord ? 'Новый пост' : $blog->name;
$this->params['breadcrumbs'][] = ['label' => 'Блог', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="subject-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="subject-form">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <?= $form->field($blog, 'name')->textInput(['required' => true, 'maxlength' => true]) ?>

        <?=
        $form->field($blog, 'imageFile', ['options' => ['class' => 'form-group col-xs-10']])
            ->fileInput(['required' => $blog->isNewRecord, 'accept' => 'image/jpeg,image/png', 'data' => ['id' => $blog->id]]);
        ?>

        <div class="col-xs-2">
            <?php if ($blog->image): ?>
                <img src="<?= $blog->imageUrl; ?>" style="max-width: 100%;">
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>

        <?=
        $form->field($blog, 'content')->widget(\dosamigos\tinymce\TinyMce::class, \common\components\DefaultValuesComponent::getTinyMceSettings());
        ?>

        <?= $this->render('/webpage/_form', [
            'form' => $form,
            'webpage' => $blog->webpage,
            'module' => isset($module) ? $module : null,
        ]); ?>

        <div class="form-group">
            <?= Html::submitButton($blog->isNewRecord ? 'Создать' : 'Сохранить', ['class' => $blog->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

</div>
