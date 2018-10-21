<?php

/* @var $this \frontend\components\extended\View */
/* @var $webpage common\models\Webpage */
/* @var $subject common\models\Subject */
/* @var $quizList \common\models\Quiz[] */

if ($subject):
    $this->title .= ' - ' . $subject->name; ?>
    <h1>Тесты по предмету "<?= $subject->name; ?>"</h1>
    <?php if (empty($quizList)): ?>
        К сожалению, по этому предмету тесты ещё не добавлены.
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($quizList as $quiz): ?>
                <a href="<?= \yii\helpers\Url::to(['view', 'quizId' => $quiz->id]); ?>" class="list-group-item">
                    <?= $quiz->name; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <h1>Тесты для проверки своего уровня</h1>
    <div class="list-group">
        <?php foreach ($quizList as $quiz): ?>
            <a href="<?= \yii\helpers\Url::to(['view', 'quizId' => $quiz->id]); ?>" class="list-group-item">
                <?= $quiz->subject->name; ?>: <?= $quiz->name; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif;