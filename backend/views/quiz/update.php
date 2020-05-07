<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $quiz common\models\Quiz */
/* @var $newQuestion \common\models\Question */
/* @var $subjects \common\models\Subject[] */
/* @var $editQuestion \common\models\Question */

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
        <div class="col-xs-12 col-sm-12 col-md-6">
            <div class="well">
                <?php if (count($quiz->questions)): ?>
                    <ol class="nested-sortable-container" data-url="<?= \yii\helpers\Url::to(['question-reorder', 'quizId' => $quiz->id]); ?>">
                        <?php foreach ($quiz->questions as $question): ?>
                            <li id="question_<?= $question->id; ?>">
                                <div>
                                    <?= $question->content; ?>
                                    <button class="float-right btn btn-default btn-xs" onclick="Quiz.deleteQuestion(<?= $question->id; ?>);">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                    <button class="float-right btn btn-default btn-xs" onclick="Quiz.editQuestion(<?= $question->id; ?>);">
                                        <span class="glyphicon glyphicon-pencil"></span>
                                    </button>
                                    <span class="clearfix"></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <hr>
                <?php endif; ?>
                <button class="btn btn-info" onclick="$('#new_element_form').removeClass('hidden'); $(this).hide();">Добавить новый вопрос</button>
                <?php if (!isset($newQuestion)) {$newQuestion = new \common\models\Question(); $newQuestion->quiz_id = $quiz->id;} ?>
                <fieldset <?php if (!$newQuestion->content): ?>class="hidden"<?php endif; ?> id="new_element_form">
                    <legend>Добавить новый вопрос</legend>
                    <?= $this->render('/quiz/question_form', [
                        'question' => $newQuestion,
                        'config' => ['action' => \yii\helpers\Url::to(['add-question'])],
                    ]); ?>
                </fieldset>
            </div>
        </div>
        <div id="edit_element_form" class="col-xs-12 col-sm-12 col-md-6 <?php if (!isset($editQuestion)): ?> hidden<?php endif; ?>">
            <?php if (isset($editQuestion)): ?>
                <?= $this->render('/quiz/question_form', [
                    'question' => $editQuestion,
                    'config' => ['action' => \yii\helpers\Url::to(['update-question', 'questionId' => $editQuestion->id])],
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
