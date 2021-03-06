<?php

/* @var $this \frontend\components\extended\View */
/* @var $quizResult \common\models\QuizResult */

$this->params['showWelcome'] = false;
?>

<div class="container">
    <div class="content-box">

<?php if ($quizResult):
    $this->title = $quizResult->quiz_name; ?>
    <h1>Тест "<?= $quizResult->quiz_name; ?>"</h1>
    <div class="row quiz-area" id="quiz_area">
        <?php if ($quizResult->finished_at): ?>
            <div class="col">
                <div class="alert alert-info">
                    Тест завершён.<br>
                    Ваш результат - <b><?= $quizResult->rightAnswerCount; ?> правильных из <?= count($quizResult->answersArray); ?></b>.<br>
                    Приходите в <a href="<?= Yii::$app->homeUrl; ?>contacts">наш офис</a>, назовите ваше имя <b><?= $quizResult->student_name; ?></b> и мы подберём вам подходящую группу по результатам теста.
                </div>
            </div>
        <?php else:
            $script = 'Quiz.answerUrl = "' . \yii\helpers\Url::to(['save-result', 'quizHash' => $quizResult->hash]) . '";' . "\n";
            $script .= 'Quiz.completeUrl = "' . \yii\helpers\Url::to(['complete', 'quizHash' => $quizResult->hash]) . '";' . "\n";
            foreach ($quizResult->questionsArray as $key => $questionInfo) {
                $answers = [];
                foreach ($questionInfo['answers'] as $answerData) $answers[] = json_encode($answerData[0]);
                $script .= 'Quiz.questionList.push({question: ' . json_encode($questionInfo['question']) . ', answers: [' . implode(', ', $answers) . "]});\n";
            }
            foreach ($quizResult->answersArray as $key => $answer) {
                $script .= "Quiz.answerList.push($answer);\n";
            }

            $script .= 'Quiz.timeLeft = ' . $quizResult->timeLeft . ";\n"
                . "Quiz.loadQuiz();\n";
            $this->registerJs($script, \frontend\components\extended\View::POS_END);
            ?>

            <div class="d-none d-md-block col-md-3 col-lg-2">
                <div class="list-group" id="question_list">
                </div>
            </div>
            <div class="col-12 col-md-9 col-lg-10">
                <div id="time_left" class="float-right collapse"></div>
                <div id="question_content">Загрузка...</div>
                <fieldset id="answer_list" class="collapse"></fieldset>

                <?php
                $ymId = \Yii::$app->params['ym_id'];
                $this->registerJs(<<<SCRIPT
                    function ymTrackQuizEnd() {
                        if (typeof ym !== "undefined") { ym({$ymId}, "reachGoal", "QUIZ_END"); }
                    }
                    function fbTrackQuizEnd() {
                        if (typeof fbq !== "undefined") { fbq("trackCustom", "quizEnd", {subject: "{$quizResult->subject_name}"}); }
                    }
SCRIPT
                    , \yii\web\View::POS_HEAD, 'track_quiz_end');
                ?>

                <div class="row mt-3 justify-content-between">
                    <div class="col-auto">
                        <button class="btn btn-success collapse" id="complete_button" onclick="ymTrackQuizEnd(); fbTrackQuizEnd(); Quiz.errorCount = 0; Quiz.complete();">Завершить тест</button>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-success collapse" id="answer_button" onclick="Quiz.errorCount = 0; return Quiz.saveAnswer();">Ответить</button>
                    </div>
                </div>
                <div id="msg_place" class="mt-3"></div>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <h1>Ошибка</h1>
    <div class="alert alert-danger">
        Некорректный запрос
    </div>
<?php endif; ?>
    
    </div>
</div>
