<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

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

        <?= $form->field($order, 'subject', ['options' => ['class' => 'col-xs-12 col-md-6']])->textInput(['maxlength' => true]) ?>

        <?= $form->field($order, 'name', ['options' => ['class' => 'col-xs-12 col-md-6']])->textInput(['maxlength' => true]) ?>

        <?php 
//        $form->field($order, 'phoneFormatted', ['options' => ['class' => 'col-xs-12 col-md-6'], 'inputTemplate' => '<div class="input-group"><span class="input-group-addon">+998</span>{input}</div>'])
//            ->textInput(['required' => true, 'maxlength' => 11, 'pattern' => '\d{2} \d{3}-\d{4}']);
         ?>

        <?= $form->field($order, 'phone', ['options' => ['class' => 'col-xs-12 col-md-6']])
            ->textInput(['required' => true, 'maxlength' => true]); ?>

        <?= $form->field($order, 'status', ['options' => ['class' => 'col-xs-12 col-md-6']])->dropDownList(\common\models\Order::$statusLabels) ?>

        <?= $form->field($order, 'user_comment', ['options' => ['class' => 'col-xs-12 col-md-6']])->textarea(['maxlength' => true, 'disabled' => '']) ?>

        <?= $form->field($order, 'admin_comment', ['options' => ['class' => 'col-xs-12 col-md-6']])->textarea(['maxlength' => true]) ?>

        <div class="form-group col-xs-12">
            <?= Html::submitButton('сохранить', ['class' => 'btn btn-primary col-xs-6 col-xs-offset-3 col-sm-4 col-sm-offset-0 col-md-2 col-lg-1']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
