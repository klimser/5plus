<?php

use backend\components\DefaultValuesComponent;
use dosamigos\datetimepicker\DateTimePicker;
use dosamigos\tinymce\TinyMce;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use \yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $botMailing \common\models\BotMailing */

$this->title = 'Отправить сообщение всем подписчикам в Telegram';
$this->params['breadcrumbs'][] = ['label' => 'Рассылки', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="push-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="push-form">

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <?= $form->field($botMailing, 'imageFile')->fileInput(); ?>

        <?=
        $form->field($botMailing, 'message_text')
            ->widget(TinyMce::class, [
            'options' => ['rows' => 6],
            'language' => 'ru',
            'clientOptions' => [
                'element_format' => 'html',
                'block_formats' => 'Preformatted=pre',
                'toolbar' => 'undo redo | bold italic underline strikethrough link',
                'menubar' => 'edit format',
                'menu' => [
                    'edit' => ['title' => 'Edit', 'items' => 'undo redo | cut copy paste | selectall | searchreplace'],
                    'format' => ['title' => 'Format', 'items' => 'bold italic underline strikethrough codeformat pre blockformats | removeformat'],
                ],
                'formats' => [
                    'bold' => ['inline' => 'b', 'remove' => 'all'],
                    'italic' => ['inline' => 'i', 'remove' => 'all'],
                    'underline' => ['inline' => 'u', 'remove' => 'all'],
                    'strikethrough' => ['inline' => 's', 'remove' => 'all'],
                ],
                'valid_elements' => 'a[href],b,i,u,s,code,pre,br',
                'forced_root_block' => false,
            ],
        ]);
        ?>

        <?= $form->field($botMailing, 'started_at')
            ->hint('Оставьте пустым чтобы начать рассылку прямо сейчас')
            ->widget(DateTimePicker::class, ArrayHelper::merge(
                DefaultValuesComponent::getDatePickerSettings(),
                [
                    'options' => [
                        'value' => $botMailing->started_at ? $botMailing->startDate->format('d.m.Y H:i') : null,
                    ],
                    'clientOptions' => [
                        'format' => 'dd.mm.yyyy hh:ii',
                        'startDate' => date('d.m.Y H:i'),
                    ],
                ]
            ));?>

        <div class="form-group">
            <?= Html::submitButton('отправить', ['class' => 'btn btn-primary']); ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
