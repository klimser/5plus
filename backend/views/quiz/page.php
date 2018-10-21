<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $quizes \common\models\Quiz[] */
/* @var $webpage \common\models\Webpage|null */
/* @var $prefix string */

$this->title = 'Настройки страницы "Тесты"';
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Настройки страницы';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-xs-12">
            <?php
            $sortableItems = [];
            foreach ($quizes as $quiz) {
                $sortableItems[] = [
                    'content' => '<span class="glyphicon glyphicon-sort"></span> ' . $quiz->name . ' (<small>' . $quiz->subject->subjectCategory->name . '/' . $quiz->subject->name . '</small>)',
                    'options' => ['id' => $prefix . $quiz->id],
                ];
            }
            ?>
            <?= \yii\jui\Sortable::widget([
                'items' => $sortableItems,
                'options' => ['tag' => 'ol'],
                'itemOptions' => ['tag' => 'li'],
                'clientOptions' => ['cursor' => 'move'],
            ]); ?>
        </div>
    </div>
    <hr>

    <?= $this->render('/webpage/_form', [
        'webpage' => $webpage,
        'form' => $form,
    ]); ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
