<?php

use common\components\DefaultValuesComponent;
use dosamigos\tinymce\TinyMce;
use yii\bootstrap4\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $question common\models\Question */
/* @var $form yii\widgets\ActiveForm */
/* @var $config array() */
?>

<div class="question-form">
    <?php $form = ActiveForm::begin($config); ?>

    <?= Html::activeHiddenInput($question, 'quiz_id'); ?>

    <?= $form->field($question, 'content')->widget(TinyMce::class, array_merge(DefaultValuesComponent::getTinyMceSettings(), ['options' => ['rows' => 6, 'id' => 'question-content-' . $question->id]])); ?>

    <?php /*
    $form->field($model, 'content')->textarea(['rows' => 6, 'id' => 'question-content-' . $model->id]); */ ?>
    
    <div class="bg-success text-white form-group">
        <?= Html::label('Правильный ответ', null, ['class' => 'pt-1 pl-2']); ?>
        <?= Html::textInput('rightAnswer', $question->rightAnswer ? $question->rightAnswer->content : '', ['class' => 'form-control', 'required' => true]); ?>
    </div>
    <?= Html::label('Неправильные ответы'); ?>
    <div class="answers">
        <?php foreach ($question->wrongAnswers as $answer): ?>
            <div class="row">
                <div class="col-9 col-lg-10"><?= Html::textInput('wrongAnswers[]', $answer->content, ['class' => 'form-control']); ?></div>
                <div class="col-3 col-lg-2">
                    <button class="btn btn-outline-dark" onclick="return Quiz.removeAnswer(this);"><span class="fas fa-times"></span></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-sm btn-info" onclick="return Quiz.addAnswer(this);">Добавить неправильный ответ</button>

    <div class="form-group text-right">
        <?= Html::submitButton(($question->isNewRecord ? 'Добавить' : 'Сохранить') . ' вопрос', ['class' => 'btn ' . ($question->isNewRecord ? 'btn-success' : 'btn-primary')]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
