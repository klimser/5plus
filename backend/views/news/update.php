<?php

use common\components\DefaultValuesComponent;
use dosamigos\tinymce\TinyMce;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $news common\models\Promotion */

$this->title = $news->isNewRecord ? 'Новая новость' : $news->name;
$this->params['breadcrumbs'][] = ['label' => 'Новости', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="subject-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="subject-form">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <?= $form->field($news, 'name')->textInput(['required' => true, 'maxlength' => true]) ?>

        <div class="row">
            <?=
            $form->field($news, 'imageFile', ['options' => ['class' => 'form-group col-10']])
                ->fileInput(['required' => $news->isNewRecord, 'accept' => 'image/jpeg,image/png', 'data' => ['id' => $news->id]]);
            ?>
            <div class="col-2">
                <?php if ($news->image): ?>
                    <img class="img-fluid" src="<?= $news->imageUrl; ?>">
                <?php endif; ?>
            </div>
        </div>

        <?=
        $form->field($news, 'content')->widget(TinyMce::class, DefaultValuesComponent::getTinyMceSettings());
        ?>

        <?= $this->render('/webpage/_form', [
            'form' => $form,
            'webpage' => $news->webpage,
            'module' => isset($module) ? $module : null,
        ]); ?>

        <div class="form-group">
            <?= Html::submitButton($news->isNewRecord ? 'Создать' : 'Сохранить', ['class' => $news->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

</div>
