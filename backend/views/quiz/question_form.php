<?php

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

    <?= $form->field($question, 'content')->widget(\dosamigos\tinymce\TinyMce::class, array_merge(\common\components\DefaultValuesComponent::getTinyMceSettings(), ['options' => ['rows' => 6, 'id' => 'question-content-' . $question->id]])); ?>

    <?php /*
    $form->field($model, 'content')->textarea(['rows' => 6, 'id' => 'question-content-' . $model->id]); */ ?>
    
    <div class="bg-success form-group">
        <?= Html::label('Правильный ответ', null, ['class' => 'control-label']); ?>
        <?= Html::textInput('rightAnswer', $question->rightAnswer ? $question->rightAnswer->content : '', ['class' => 'form-control', 'required' => true]); ?>
    </div>
    <?= Html::label('Неправильные ответы'); ?>
    <div class="answers">
        <?php foreach ($question->wrongAnswers as $answer): ?>
            <div class="row">
                <div class="col-xs-9 col-md-10"><?= Html::textInput('wrongAnswers[]', $answer->content, ['class' => 'form-control']); ?></div>
                <div class="col-xs-3 col-md-2">
                    <button class="btn btn-default" onclick="return Quiz.removeAnswer(this);"><span class="glyphicon glyphicon-remove"></span></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-sm btn-info" onclick="return Quiz.addAnswer(this);">Добавить неправильный ответ</button>

    <div class="form-group">
        <?= Html::submitButton(($question->isNewRecord ? 'Добавить' : 'Сохранить') . ' вопрос', ['class' => 'float-right btn ' . ($question->isNewRecord ? 'btn-success' : 'btn-primary')]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
