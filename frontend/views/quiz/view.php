<?php

use \yii\bootstrap\ActiveForm;

/* @var $this \frontend\components\extended\View */
/* @var $quiz \common\models\Quiz */
/* @var $quizResult \common\models\QuizResult */

$this->params['showWelcome'] = false;

if ($quiz):
    $this->title = $quiz->name; ?>
    <h1>Тест "<?= $quiz->name; ?>" (<?= $quiz->subject->name; ?>)</h1>

    <?php $form = ActiveForm::begin([
        'validateOnBlur' => false,
        'validateOnChange' => false,
        'validateOnSubmit' => false,
        'validateOnType' => false,
        'options' => [
            'class' => 'quiz-begin-form',
        ],
    ]); ?>
        <br>
        <p>Тест состоит из <?= $quiz->questionCount; ?> вопросов. На решение теста отводится <?= \common\models\Quiz::$testTime; ?> минут. Начиная тест, убедитесь в том, что у вас есть <?= \common\models\Quiz::$testTime; ?> минут времени для его решения, тест нельзя приостановить и продолжить позже.</p><br>

        <?= $form->field($quizResult, 'student_name')->textInput(['maxlength' => true, 'placeholder' => 'ФИО', 'required' => true])->label('Укажите ваши фамилию, имя, отчество'); ?>

        <?= \yii\bootstrap\Html::submitButton('Начать тест', ['class' => 'btn btn-success']); ?>

    <?php ActiveForm::end(); ?>
<?php else: ?>
    <h1>Ошибка</h1>
    <div class="alert alert-danger">
        Некорректный запрос
    </div>
<?php endif;