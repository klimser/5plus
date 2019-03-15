<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $giftCardType common\models\GiftCardType */

$this->title = 'Новый';
$this->params['breadcrumbs'][] = ['label' => 'Типы предоплаченных карт', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="gift-card-type-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="subject-form">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($giftCardType, 'name')->textInput(['required' => true, 'maxlength' => true]); ?>

        <?= $form->field($giftCardType, 'amount')->input('number', ['required' => true, 'min' => 1000, 'step' => 1000]); ?>

        <div class="form-group">
            <?= Html::submitButton('добавить', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

</div>
