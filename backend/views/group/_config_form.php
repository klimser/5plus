<?php
/* @var $this \yii\web\View */
/* @var $form \yii\bootstrap4\ActiveForm */
/* @var $groupConfig \common\models\GroupConfig */
/* @var $teacherList array<\common\models\Teacher> */
/* @var $visible bool */
/* @var $dateFromAllowed bool */

use common\components\DefaultValuesComponent;
use common\components\helpers\Calendar;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

?>

<div id="form_group_config" class="collapse <?= $visible ? 'show' : ''; ?>">
    <?= $form->field($groupConfig, 'date_from', ['options' => ['class' => 'collapse ' . ($dateFromAllowed ? 'show' : '')]])
        ->widget(
            DatePicker::class, ArrayHelper::merge(
            DefaultValuesComponent::getDatePickerSettings(),
            ['options' => [
                'required' => true,
                'autocomplete' => 'off',
                'disabled' => !$dateFromAllowed || !$visible,
            ]]
        )); ?>

    <?= $form->field($groupConfig, 'teacher_id', ['options' => ['class' => 'form-group']])
        ->dropDownList($teacherList, ['required' => true, 'disabled' => !$visible]); ?>

    <?= $form->field($groupConfig, 'teacher_rate', ['options' => ['class' => 'form-group']])
        ->input('number', ['required' => true, 'disabled' => !$visible, 'min' => 0, 'max' => 100, 'step' => '0.01']); ?>

    <?= $form->field($groupConfig, 'room_number', ['options' => ['class' => 'form-group']])
        ->textInput(['maxlength' => true, 'disabled' => !$visible]); ?>

    <?= $form->field($groupConfig, 'lesson_price', ['options' => ['class' => 'form-group']])
        ->input('number', ['placeholder' => 'Только целое число, например: 24000', 'required' => true, 'disabled' => !$visible]); ?>

    <?= $form->field($groupConfig, 'lesson_price_discount', ['options' => ['class' => 'form-group']])
        ->input('number', ['placeholder' => 'Только целое число, например: 18000', 'disabled' => !$visible]); ?>

    <?= $form->field($groupConfig, 'lesson_duration', ['options' => ['class' => 'form-group'], 'inputTemplate' => '<div class="input-group">{input}<span class="input-group-append"><span class="input-group-text">мин</span></span></div>'])
        ->input('number', ['placeholder' => 'Только целое число, например: 40', 'required' => true, 'disabled' => !$visible]); ?>

    <div class="form-group">
        <label>Расписание</label>
        <?php for ($i = 0; $i < 7; $i++): ?>
            <div class="input-group mb-2">
                <div class="input-group-prepend"><span class="input-group-text"><?= Calendar::$weekDaysShort[($i + 1) % 7]; ?></span></div>
                <input type="time" class="form-control" name="weektime[<?= $i; ?>]" value="<?= $groupConfig->schedule[$i]; ?>" placeholder="Время чч:мм" pattern="\d{2}:\d{2}" maxlength="5" <?= $visible ? '' : 'disabled'; ?>>
            </div>
        <?php endfor; ?>
    </div>
</div>