<?php

use common\models\Order;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $order common\models\Order */

$this->title = 'Обновить заявку от ' . $order->name;
$this->params['breadcrumbs'][] = ['label' => 'Заявки', 'url' => ['index']];
$this->params['breadcrumbs'][] = $order->name;
?>
<div class="order-update">

    <h1><?= Html::encode($this->title) ?></h1>
    <div class="help-block">Добавлен <?= $order->createDateString; ?></div>

    <div class="order-form">

        <?php $form = ActiveForm::begin(['options' => ['class' => 'row']]); ?>

        <?= $form->field($order, 'subject', ['options' => ['class' => 'form-group col-12 col-md-6']])->textInput(['maxlength' => true]) ?>

        <?= $form->field($order, 'name', ['options' => ['class' => 'form-group col-12 col-md-6']])->textInput(['maxlength' => true]) ?>

        <?= $form->field($order, 'phone', ['options' => ['class' => 'form-group col-12 col-md-6']])
            ->textInput(['required' => true, 'maxlength' => true]); ?>

        <?= $form->field($order, 'status', ['options' => ['class' => 'form-group col-12 col-md-6']])->dropDownList(Order::$statusLabels) ?>

        <?= $form->field($order, 'user_comment', ['options' => ['class' => 'form-group col-12 col-md-6']])->textarea(['maxlength' => true, 'disabled' => '']) ?>

        <?= $form->field($order, 'admin_comment', ['options' => ['class' => 'form-group col-12 col-md-6']])->textarea(['maxlength' => true]) ?>

        <div class="form-group col">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
