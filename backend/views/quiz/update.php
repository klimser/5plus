<?php

use common\models\Question;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $quiz common\models\Quiz */
/* @var $newQuestion Question */
/* @var $subjects \common\models\Subject[] */
/* @var $editQuestion Question */

$this->registerJs('Quiz.id = ' . $quiz->id . ';  NestedSortable.init("ol.nested-sortable-container");');

$this->title = 'Изменить тест: ' . ' ' . $quiz->name;
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $quiz->name;
?>

<div class="quiz-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'quiz' => $quiz,
        'subjects' => $subjects,
    ]) ?>

    <div class="row">
        <div class="col-12 col-lg-6">
            <?php if (count($quiz->questions)): ?>
                <ol class="nested-sortable-container" data-url="<?= Url::to(['question-reorder', 'quizId' => $quiz->id]); ?>">
                    <?php foreach ($quiz->questions as $question): ?>
                        <li id="question_<?= $question->id; ?>">
                            <div>
                                <?= $question->content; ?>
                                <button class="float-right btn btn-outline-dark btn-sm" onclick="Quiz.deleteQuestion(<?= $question->id; ?>);">
                                    <span class="fas fa-times"></span>
                                </button>
                                <button class="float-right btn btn-outline-dark btn-sm" onclick="Quiz.editQuestion(<?= $question->id; ?>);">
                                    <span class="fas fa-pencil-alt"></span>
                                </button>
                                <span class="clearfix"></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <hr>
            <?php endif; ?>
            <button class="btn btn-info" onclick="$('#new_element_form').collapse('show'); $(this).hide();">Добавить новый вопрос</button>
            <?php if (!isset($newQuestion)) {$newQuestion = new Question(); $newQuestion->quiz_id = $quiz->id;} ?>
            <fieldset class="collapse <?php if ($newQuestion->content): ?> show <?php endif; ?>" id="new_element_form">
                <legend>Добавить новый вопрос</legend>
                <?= $this->render('/quiz/question_form', [
                    'question' => $newQuestion,
                    'config' => ['action' => Url::to(['add-question'])],
                ]); ?>
            </fieldset>
        </div>
        <div id="edit_element_form" class="col-12 col-lg-6 collapse <?php if (isset($editQuestion)): ?> show <?php endif; ?>">
            <?php if (isset($editQuestion)): ?>
                <?= $this->render('/quiz/question_form', [
                    'question' => $editQuestion,
                    'config' => ['action' => Url::to(['update-question', 'questionId' => $editQuestion->id])],
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
