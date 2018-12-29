<?php

use \yii\bootstrap\ActiveForm;

/* @var $this \frontend\components\extended\View */
/* @var $webpage common\models\Webpage */
/* @var $subject common\models\Subject */
/* @var $quizList \common\models\Quiz[] */
/* @var $quizResult \common\models\QuizResult */

?>

<div class="row step-line">
    <div class="col-xs-4 text-center step step-left">
        <button data-href="step-1" type="button" class="btn btn-primary rounded-circle" id="step-button-1" onclick="QuizList.jump(this);">1</button>
        <div class="hidden-xs step-details">
            <div class="title">Предмет</div>
            <small class="value" id="selected-subject"></small>
        </div>
    </div>
    <div class="col-xs-4 text-center step step-center">
        <button data-href="step-2" type="button" class="btn btn-default rounded-circle" id="step-button-2" disabled  onclick="QuizList.jump(this);">2</button>
        <div class="hidden-xs step-details">
            <div class="title">Уровень</div>
            <small class="value" id="selected-quiz"></small>
        </div>
    </div>
    <div class="col-xs-4 text-center step step-right">
        <button data-href="step-3" type="button" class="btn btn-default rounded-circle" id="step-button-3" disabled  onclick="QuizList.jump(this);">3</button>
        <div class="hidden-xs step-details">
            <div class="title">к решению!</div>
        </div>
    </div>
</div>

<?php $form = ActiveForm::begin(['action' => \yii\helpers\Url::to(['quiz/view'])]); ?>
    <input type="hidden" name="quiz_id" required>
    <div class="row step-content" id="step-1">
        <div class="col-xs-12">
            <h2>На старт!</h2>
            <div class="list-group">
                <?php
                $subjectSet = [];
                $script = '';

                foreach ($quizList as $quiz):
                    if (!array_key_exists($quiz->subject_id, $subjectSet)):
                        $script .= "QuizList.data.set({$quiz->subject_id}, {name: \"{$quiz->subject->name}\", quizzes: new Map()});\n";
                        $subjectSet[$quiz->subject_id] = true; ?>
                            <a href="#" data-subject="<?= $quiz->subject_id; ?>" class="list-group-item" onclick="QuizList.setSubject(this);">
                                <?= $quiz->subject->name; ?>
                            </a>
                    <?php endif;
                    $script .= "QuizList.data.get({$quiz->subject_id}).quizzes.set({$quiz->id}, {name: \"{$quiz->name}\", questionCount: {$quiz->questionCount}});\n";
                endforeach;
                $this->registerJs($script, \yii\web\View::POS_END);
                ?>
            </div>
        </div>
    </div>
    <div class="row step-content hidden" id="step-2">
        <div class="col-xs-12">
            <h2>Внимание!</h2>
            <div class="list-group" id="quiz-list"></div>
        </div>
    </div>
    <div class="row step-content hidden" id="step-3">
        <div class="col-xs-12">
            <h2>Марш!</h2>
            <p>Тест состоит из <span id="question-count"></span> вопросов. На решение теста отводится <?= \common\models\Quiz::TEST_TIME; ?> минут. Начиная тест, убедитесь в том, что у вас есть <?= \common\models\Quiz::TEST_TIME; ?> минут времени для его решения, тест нельзя приостановить и продолжить позже.</p><br>

            <?= $form->field($quizResult, 'student_name')
                ->textInput(['maxlength' => true, 'placeholder' => 'ФИО', 'required' => true])
                ->label('Укажите ваши фамилию, имя, отчество'); ?>

            <?= \yii\bootstrap\Html::submitButton('Начать тест', ['class' => 'btn btn-success']); ?>
        </div>
    </div>
<?php ActiveForm::end(); ?>

<?php if ($subject):
    $this->title .= ' - ' . $subject->name; ?>

<?php endif;