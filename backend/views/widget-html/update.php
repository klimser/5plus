<?php

use yii\helpers\Html;
use \yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $widget common\models\WidgetHtml */

$this->title = $widget->isNewRecord ? 'Создать блок' : 'Изменить блок: ' . $widget->name;
$this->params['breadcrumbs'][] = ['label' => 'Блоки', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<script>
    var WidgetHtml = {
        submitForm: function(e) {
            $("#widgethtml-editor").val($("#with_editor").hasClass('active') ? 1 : 0);
            return true;
        }
    };
</script>
<div class="widget-html-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="widget-html-form">
        <?php $form = ActiveForm::begin(['options' => ['onSubmit' => 'return WidgetHtml.submitForm(this);']]); ?>

        <?php if ($widget->isNewRecord): ?>
            <?= $form->field($widget, 'name')->textInput(['required' => true, 'maxlength' => true]) ?>
        <?php endif; ?>

        <?= $form->field($widget, 'editor', ['template' => '{input}', 'options' => ['class' => []]])->hiddenInput(); ?>

        <div>
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" <?= $widget->editor ? '' : 'class="active"'; ?>><a href="#without_editor" role="tab" data-toggle="tab">Без редактора</a></li>
                <li role="presentation" <?= $widget->editor ? 'class="active"' : ''; ?>><a href="#with_editor" role="tab" data-toggle="tab">С редактором</a></li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane <?= $widget->editor ? '' : 'active'; ?>" id="without_editor">
                    <?= $form->field($widget, 'content[plain]', ['enableLabel' => false])->textarea(['rows' => 10, 'value' => $widget->content]); ?>
                </div>
                <div role="tabpanel" class="tab-pane <?= $widget->editor ? 'active' : ''; ?>" id="with_editor">
                    <?=
                    $form->field($widget, 'content[editor]', ['enableLabel' => false])
                        ->widget(\dosamigos\tinymce\TinyMce::class, array_merge(
                                \common\components\DefaultValuesComponent::getTinyMceSettings(),
                                ['options' => ['rows' => 10, 'value' => $widget->content]])
                        );
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton($widget->isNewRecord ? 'Создать' : 'Сохранить', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
