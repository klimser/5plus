<?php

use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use yii\jui\Sortable;

/* @var $this yii\web\View */
/* @var $quizes \common\models\Quiz[] */
/* @var $webpage \common\models\Webpage|null */
/* @var $prefix string */

$this->title = 'Настройки страницы "Тесты"';
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Настройки страницы';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(['options' => ['onSubmit' => 'return Main.submitSortableForm(this);']]); ?>
    <?php
    $sortableItems = [];
    foreach ($quizes as $quiz) {
        $sortableItems[] = [
            'content' => '<span class="fas fa-arrows-alt"></span> ' . $quiz->name . ' (<small>' . $quiz->subject->subjectCategory->name . '/' . $quiz->subject->name . '</small>)',
            'options' => ['id' => $prefix . $quiz->id],
        ];
    }
    ?>
    <?= Sortable::widget([
        'items' => $sortableItems,
        'options' => ['tag' => 'ol'],
        'itemOptions' => ['tag' => 'li', 'class' => 'border px-2 py-1 my-1'],
        'clientOptions' => ['cursor' => 'move'],
    ]); ?>
    <hr>

    <?= $this->render('/webpage/_form', [
        'webpage' => $webpage,
        'form' => $form,
    ]); ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
