<?php

use yii\helpers\Html;
use \yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */

$this->title = 'Отправить сообщение всем подписчикам в Telegram';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="push-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="push-form">

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <div class="form-group">
            <label for="image">Картинка (max <b>5</b>MB)</label>
            <?= Html::fileInput('image', 'null', ['id' => 'image']); ?>
        </div>

        <div class="form-group">
            <label for="text">Сообщение</label>
            <p class="help-block alert alert-info">Не объединяйте стили, например "жирный и курсив", Telegram это не покажет.</p>
            <?=
            \dosamigos\tinymce\TinyMce::widget([
                'id' => 'text',
                'name' => 'text',
                'options' => ['rows' => 6],
                'language' => 'ru',
                'clientOptions' => [
                    'element_format' => 'html',
                    'block_formats' => 'Preformatted=pre',
                    'toolbar' => 'undo redo | bold italic link',
                    'menubar' => 'edit format',
                    'menu' => [
                        'edit' => ['title' => 'Edit', 'items' => 'undo redo | cut copy paste | selectall | searchreplace'],
                        'format' => ['title' => 'Format', 'items' => 'bold italic codeformat pre blockformats | removeformat'],
                    ],
                    'formats' => [
                        'bold' => ['inline' => 'b', 'remove' => 'all'],  
                        'italic' => ['inline' => 'i', 'remove' => 'all'],  
                    ],
                    'valid_elements' => 'a[href],b,i,code,pre,br',
                    'forced_root_block' => false,
                ],
            ]);
            ?>
        </div>

        <div class="form-group">
            <?= Html::submitButton('отправить', ['class' => 'btn btn-primary']); ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
