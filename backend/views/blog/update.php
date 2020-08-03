<?php

use common\components\DefaultValuesComponent;
use dosamigos\tinymce\TinyMce;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $blog common\models\Blog */

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
        $form->field($blog, 'imageFile', ['options' => ['class' => 'form-group col-10']])
            ->fileInput(['required' => $blog->isNewRecord, 'accept' => 'image/jpeg,image/png', 'data' => ['id' => $blog->id]]);
        ?>
        <div class="col-2">
            <?php if ($blog->image): ?>
                <img class="img-fluid" src="<?= $blog->imageUrl; ?>">
            <?php endif; ?>
        </div>

        <?=
        $form->field($blog, 'content')->widget(TinyMce::class, DefaultValuesComponent::getTinyMceSettings());
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
