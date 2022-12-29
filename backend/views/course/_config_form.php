<?php
/* @var $this \yii\web\View */
/* @var $form \yii\bootstrap4\ActiveForm */
/* @var $courseConfig \common\models\CourseConfig */
/* @var $teacherList array<\common\models\Teacher> */
/* @var $disabled bool */

use common\components\DefaultValuesComponent;
use common\components\helpers\Calendar;
use yii\helpers\ArrayHelper;
use yii\jui\DatePicker;

$dateClientOptions = $courseConfig->dateFromObject ? ['minDate' => $courseConfig->dateFromObject->format('d.m.Y')] : [];

?>

<div id="form_course_config">
    <?= $form->field($courseConfig, 'date_from', ['options' => ['class' => 'collapse ' . ($disabled ? 'show' : '')]])
        ->widget(
            DatePicker::class, ArrayHelper::merge(
            DefaultValuesComponent::getDatePickerSettings(),
            [
                'options' => [
                    'required' => true,
                    'autocomplete' => 'off',
                    'disabled' => true,
                ],
                'clientOptions' => $dateClientOptions,
            ]
        )); ?>

    <?= $form->field($courseConfig, 'name', ['options' => ['class' => 'form-group']])
        ->textInput(['maxlength' => true, 'required' => true, 'disabled' => $disabled]) ?>

    <?= $form->field($courseConfig, 'legal_name', ['options' => ['class' => 'form-group']])
        ->textInput(['maxlength' => true, 'required' => true, 'disabled' => $disabled]) ?>

    <?= $form->field($courseConfig, 'teacher_id', ['options' => ['class' => 'form-group']])
        ->dropDownList($teacherList, ['required' => true, 'disabled' => $disabled]); ?>

    <?= $form->field($courseConfig, 'teacher_rate', ['options' => ['class' => 'form-group']])
        ->input('number', ['required' => false, 'disabled' => $disabled, 'min' => 0, 'max' => 100, 'step' => '0.01']); ?>

    <?= $form->field($courseConfig, 'teacher_lesson_pay', ['options' => ['class' => 'form-group']])
        ->input('number', ['required' => false, 'disabled' => $disabled]); ?>

    <?= $form->field($courseConfig, 'room_number', ['options' => ['class' => 'form-group']])
        ->textInput(['maxlength' => true, 'disabled' => $disabled]); ?>

    <?= $form->field($courseConfig, 'lesson_price', ['options' => ['class' => 'form-group']])
        ->input('number', ['placeholder' => 'Только целое число, например: 24000', 'required' => true, 'disabled' => $disabled]); ?>

    <?= $form->field($courseConfig, 'lesson_price_discount', ['options' => ['class' => 'form-group']])
        ->input('number', ['placeholder' => 'Только целое число, например: 18000', 'disabled' => $disabled]); ?>

    <?= $form->field($courseConfig, 'lesson_duration', ['options' => ['class' => 'form-group'], 'inputTemplate' => '<div class="input-group">{input}<span class="input-group-append"><span class="input-group-text">мин</span></span></div>'])
        ->input('number', ['placeholder' => 'Только целое число, например: 40', 'required' => true, 'disabled' => $disabled]); ?>

    <div class="form-group">
        <label>Расписание</label>
        <?php for ($i = 0; $i < 7; $i++): ?>
            <div class="input-group mb-2">
                <div class="input-group-prepend"><span class="input-group-text"><?= Calendar::$weekDaysShort[($i + 1) % 7]; ?></span></div>
                <input type="time" class="form-control" name="weektime[<?= $i; ?>]" value="<?= $courseConfig->schedule[$i]; ?>" placeholder="Время чч:мм" pattern="\d{2}:\d{2}" maxlength="5" <?= $disabled ?  'disabled' : ''; ?>>
            </div>
        <?php endfor; ?>
    </div>
</div>