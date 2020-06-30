<?php

use common\models\Quiz;
use himiklab\yii2\recaptcha\ReCaptcha;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this \frontend\components\extended\View */
/* @var $webpage common\models\Webpage */
/* @var $activeSubject common\models\Subject|null */
/* @var $activeQuiz common\models\Quiz|null */
/* @var $quizList Quiz[] */
/* @var $quizResult \common\models\QuizResult */

$script = '';
?>

<div class="container">
    <div class="content-box">
<?php $form = ActiveForm::begin([
        'action' => Url::to(['quiz/view']),
        'options' => [
            'onsubmit' => 'if (!QuizList.startAllowed(this)) return false; ymTrackQuizStart(); fbTrackQuizStart(); return true;']
        ]
); ?>
    <input type="hidden" name="quiz_id" required> 
        <nav>
            <div class="nav step-line row justify-content-between" role="tablist">
                <a class="nav-item nav-link step-tab active" id="step-1-tab" href="#step-1" role="tab"
                   aria-controls="step-1" aria-selected="true" data-step-order="1" onclick="return MultiStepForm.jump(this);">
                    <div class="step-label">1</div>
                    <div class="d-none d-md-block step-details">
                        <div class="title">Предмет</div>
                    </div>
                </a>
                <a class="nav-item nav-link step-tab" id="step-2-tab" href="#step-2" role="tab"
                   aria-controls="step-2" aria-selected="true" data-step-order="2" onclick="return MultiStepForm.jump(this);">
                    <div class="step-label">2</div>
                    <div class="d-none d-md-block step-details">
                        <div class="title">Уровень</div>
                    </div>
                </a>
                <a class="nav-item nav-link step-tab" id="step-3-tab" href="#step-3" role="tab"
                   aria-controls="step-3" aria-selected="true" data-step-order="3" onclick="return MultiStepForm.jump(this);">
                    <div class="step-label">3</div>
                    <div class="d-none d-md-block step-details">
                        <div class="title">к решению!</div>
                    </div>
                </a>
            </div>
        </nav>
        <div class="tab-content pt-3" id="nav-tabContent">
            <div class="tab-pane active" id="step-1" role="tabpanel" aria-labelledby="step-1-tab">
                <h2>На старт!</h2>
                <div class="list-group">
                    <?php
                    $subjectSet = [];

                    foreach ($quizList as $quiz):
                        if (!array_key_exists($quiz->subject_id, $subjectSet)):
                            $script .= "QuizList.data.set({$quiz->subject_id}, {name: \"{$quiz->subject->name}\", quizzes: new Map()});\n";
                            $subjectSet[$quiz->subject_id] = true; ?>
                            <a href="#" data-subject="<?= $quiz->subject_id; ?>" class="list-group-item" onclick="return QuizList.setSubject(this);">
                                <?= $quiz->subject->name; ?>
                            </a>
                        <?php endif;
                        $script .= "QuizList.data.get({$quiz->subject_id}).quizzes.set({$quiz->id}, {name: \"{$quiz->name}\", questionCount: {$quiz->questionCount}});\n";
                    endforeach; ?>
                </div>
            </div>
            <div class="tab-pane" id="step-2" role="tabpanel" aria-labelledby="step-2-tab">
                <h2>Внимание!</h2>
                <div class="list-group mb-3" id="quiz-list"></div>

                <button type="button" class="btn btn-secondary" onclick="MultiStepForm.jump($('.step-tab[data-step-order=1]'));">назад</button>
            </div>
            <div class="tab-pane" id="step-3" role="tabpanel" aria-labelledby="step-3-tab">
                <h2>Марш!</h2>
                <p>Тест состоит из <span id="question-count"></span> вопросов. На решение теста отводится <?= Quiz::TEST_TIME; ?> минут. Начиная тест, убедитесь в том, что у вас есть <?= Quiz::TEST_TIME; ?> минут времени для его решения, тест нельзя приостановить и продолжить позже.</p><br>

                <?= $form->field($quizResult, 'student_name')
                    ->textInput(['maxlength' => true, 'placeholder' => 'ФИО', 'required' => true])
                    ->label('Укажите ваши фамилию, имя, отчество'); ?>

                <?= $form->field($quizResult, 'reCaptcha', ['labelOptions' => ['label' => null]])->widget(ReCaptcha::class); ?>

                <button type="button" class="btn btn-secondary" onclick="MultiStepForm.jump($('.step-tab[data-step-order=2]'));">назад</button>
                <?= Html::submitButton('Начать тест', ['class' => 'btn btn-success']); ?>
            </div>
        </div>
<?php
ActiveForm::end();

$ymId = Yii::$app->params['ym_id'];
$this->registerJs(<<<SCRIPT
function ymTrackQuizStart() {
    if (typeof ym !== "undefined") { ym({$ymId}, "reachGoal", "QUIZ_START"); }
}
function fbTrackQuizStart() {
    if (typeof fbq !== "undefined") { fbq("trackCustom", "quizStart", {subject: QuizList.data.get(QuizList.subject).name}); }
}
SCRIPT
    , View::POS_HEAD, 'track_quiz_start');

if (isset($activeSubject) && $activeSubject) {
    $this->title .= ' - ' . $activeSubject->name;
    $script .= "QuizList.setSubject($('a[data-subject={$activeSubject->id}]'));\n";
}

if (isset($activeQuiz) && $activeQuiz) {
    $script .= "QuizList.setQuiz($('a[data-quiz={$activeQuiz->id}]'));\n";
}

$this->registerJs($script, View::POS_END);
