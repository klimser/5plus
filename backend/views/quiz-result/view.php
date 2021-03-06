<?php

use frontend\components\extended\View;
use yii\bootstrap4\Html;

/* @var $this View */
/* @var $quizResult \common\models\QuizResult */

$this->title = 'Результаты теста ' . $quizResult->quiz_name;
$this->params['breadcrumbs'][] = ['label' => 'Результаты тестов', 'url' => ['index']];
$this->params['breadcrumbs'][] = $quizResult->quiz_name;

?>
<h1><?= Html::encode($this->title) ?> (<?= $quizResult->subject_name; ?>)</h1>
<div class="row quiz-area" id="quiz_area">
    <?php
        $script = '';
        foreach ($quizResult->questionsArray as $key => $questionInfo) {
            $script .= 'QuizResult.questionList.push({question: ' . json_encode($questionInfo['question']) . ', answers: ' . json_encode($questionInfo['answers']) . "});\n";
        }
        foreach ($quizResult->answersArray as $key => $answer) {
            $script .= "QuizResult.answerList.push($answer);\n";
        }
    
        $script .= "QuizResult.loadQuiz();\n";
        $this->registerJs($script, View::POS_END);
    ?>
    <div class="col-12">
        <div class="alert alert-info">
            <b><?= $quizResult->student_name; ?></b><br>
            Начало теста <?= $quizResult->getCreateDateString(); ?><?php if ($quizResult->finished_at): ?>, завершение теста <?= $quizResult->finishDate->format('Y-m-d H:i:s'); ?><?php endif; ?><br>
            Затрачено времени: <?= $quizResult->timeUsed; ?><br>
            Результат: <b><?= $quizResult->rightAnswerCount; ?></b> правильных из <b><?= count($quizResult->answersArray); ?></b>.
        </div>
    </div>
    <div class="col-3 col-xl-2">
        <div class="list-group" id="question_list">
        </div>
    </div>
    <div class="col-9 col-xl-10">
        <div id="question_content">Загрузка...</div>
        <ul id="answer_list" class="list-group collapse"></ul>
    </div>
</div>
