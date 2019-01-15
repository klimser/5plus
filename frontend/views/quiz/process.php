<?php

/* @var $this \frontend\components\extended\View */
/* @var $quizResult \common\models\QuizResult */

$this->params['showWelcome'] = false;

if ($quizResult):
    $this->title = $quizResult->quiz_name; ?>
    <h1>Тест "<?= $quizResult->quiz_name; ?>"</h1>
    <div class="row quiz-area" id="quiz_area">
        <?php if ($quizResult->finished_at): ?>
            <div class="col-xs-12">
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

            <div class="col-md-3 col-lg-2 hidden-sm hidden-xs">
                <div class="list-group" id="question_list">
                </div>
            </div>
            <div class="col-xs-12 col-md-9 col-lg-10">
                <div id="time_left" class="pull-right hidden"></div>
                <div id="question_content">Загрузка...</div>
                <fieldset id="answer_list" class="hidden"></fieldset>

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

                <button class="btn btn-success pull-left hidden" id="complete_button" onclick="ymTrackQuizEnd(); fbTrackQuizEnd(); Quiz.errorCount = 0; Quiz.complete();">Завершить тест</button>
                <button class="btn btn-success pull-right hidden" id="answer_button" onclick="Quiz.errorCount = 0; return Quiz.saveAnswer();">Ответить</button>
                <div id="msg_place"></div>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <h1>Ошибка</h1>
    <div class="alert alert-danger">
        Некорректный запрос
    </div>
<?php endif;