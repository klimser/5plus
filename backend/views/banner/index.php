<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $banner common\models\WidgetHtml */
/* @var $form yii\bootstrap\ActiveForm */

$this->title = 'Баннер';
$this->params['breadcrumbs'][] = 'Баннер';
?>
<div class="banner-update">

    <?php $form = ActiveForm::begin(); ?>

    <?=
    $form->field($banner, 'content')->widget(\dosamigos\tinymce\TinyMce::class, [
        'options' => ['rows' => 6],
        'language' => 'ru',
        'clientOptions' => [
            'plugins' => [
                'advlist autolink lists link charmap preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table contextmenu paste',
                'image imagetools',
                'textcolor responsivefilemanager'
            ],
            'toolbar' => 'undo redo | styleselect | bold italic | fontselect fontsizeselect | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | responsivefilemanager link image',
            'imagetools_toolbar' => 'rotateleft rotateright | flipv fliph | editimage imageoptions',
            'external_filemanager_path' => Yii::$app->getHomeUrl() . 'filemanager/filemanager/',
            'filemanager_title' => 'Responsive Filemanager',
            'external_plugins' => [
                'filemanager' => Yii::$app->getHomeUrl() . 'filemanager/filemanager/plugin.min.js',
                'responsivefilemanager' => Yii::$app->getHomeUrl() . 'js/responsivefilemanager/plugin.min.js',
            ],
            'relative_urls' => false,
        ]
    ]);
    ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>