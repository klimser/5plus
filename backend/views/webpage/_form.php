<?php

/* @var $this yii\web\View */
/* @var $webpage common\models\Webpage */
/* @var $form yii\bootstrap4\ActiveForm */
/* @var $module \common\models\Module */

if ($webpage === null) $webpage = new \common\models\Webpage();

if ($webpage->module) $module = $webpage->module;
if (isset($module) && $module && ($module->field_for_url || $module->field_for_title)) {
    $js = '';
    if ($module->field_for_url) {
        $js .= 'Webpage.urlSelector = "#' . $module->field_for_url . '"; ';
    }
    if ($module->field_for_title) {
        $js .= 'Webpage.titleSelector = "#' . $module->field_for_title . '"; ';
    }
    $this->registerJs($js,\yii\web\View::POS_END);
}
?>

<div class="card mb-3">
    <div class="card-body p-3">
        <div class="row">
            <?= $form->field($webpage, 'url', ['enableAjaxValidation' => false, 'enableClientValidation' => false, 'options' => ['class' => 'form-group col-12']])
                ->textInput(['value' => $webpage->shortUrl, 'required' => true, 'maxlength' => true, 'onfocus' => 'Webpage.suggestUrl(this);']) ?>
        </div>
        <div class="row">
            <?= $form->field($webpage, 'title', ['enableAjaxValidation' => false, 'enableClientValidation' => false, 'options' => ['class' => 'form-group col-12']])
                ->textInput(['required' => true, 'maxlength' => true, 'onfocus' => 'Webpage.fillTitle(this);']) ?>
        </div>
        <div class="row">
            <?= $form->field($webpage, 'description', ['enableAjaxValidation' => false, 'enableClientValidation' => false, 'options' => ['class' => 'form-group col-12 col-sm-6']])
                ->textarea(['rows' => 4]) ?>
    
            <?= $form->field($webpage, 'keywords', ['enableAjaxValidation' => false, 'enableClientValidation' => false, 'options' => ['class' => 'form-group col-12 col-sm-6']])
                ->textarea(['rows' => 4]) ?>
        </div>
    </div>
</div>
