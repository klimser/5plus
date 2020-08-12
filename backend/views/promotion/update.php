<?php

use common\components\DefaultValuesComponent;
use dosamigos\tinymce\TinyMce;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $promotion common\models\Promotion */

$this->title = $promotion->isNewRecord ? 'Новая акция' : $promotion->name;
$this->params['breadcrumbs'][] = ['label' => 'Акции', 'url' => ['index']];
$this->params['breadcrumbs'][] = $promotion->name;

?>
<div class="subject-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="subject-form">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <?= $form->field($promotion, 'name')->textInput(['required' => true, 'maxlength' => true]) ?>

        <div class="row">
            <?=
            $form->field($promotion, 'imageFile', ['options' => ['class' => 'form-group col-10']])
                ->fileInput(['required' => $promotion->isNewRecord, 'accept' => 'image/jpeg,image/png', 'data' => ['id' => $promotion->id]]);
            ?>
    
            <div class="col-2">
                <?php if ($promotion->image): ?>
                    <img class="img-fluid" src="<?= $promotion->imageUrl; ?>">
                <?php endif; ?>
            </div>
        </div>

        <?=
        $form->field($promotion, 'content')->widget(TinyMce::class, DefaultValuesComponent::getTinyMceSettings());
        ?>

        <?= $this->render('/webpage/_form', [
            'form' => $form,
            'webpage' => $promotion->webpage,
            'module' => isset($module) ? $module : null,
        ]); ?>

        <div class="form-group">
            <?= Html::submitButton($promotion->isNewRecord ? 'Создать' : 'Сохранить', ['class' => $promotion->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

</div>
