<?php

use common\components\DefaultValuesComponent;
use yii\bootstrap4\ActiveForm;
use yii\jui\DatePicker;


/* @var $this yii\web\View */
/* @var $pupil \common\models\User */
/* @var $groups \common\models\Course[] */
/* @var $groupData array */

$this->title = 'Добавить студента в группу';
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/** @var bool $addGroup */
$addGroup = array_key_exists('add', $groupData) && $groupData['add'];

?>

<div class="pupil-add-to-group">
    <h1><?= $pupil->name; ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <div class="form-group">
        <label for="group">Группа</label>
        <select class="form-control" id="group" name="group[groupId]">
            <?php foreach ($groups as $group): ?>
                <option value="<?= $group->id; ?>" <?= array_key_exists('id', $groupData) && intval($groupData['groupId']) == $group->id ? 'selected' : ''; ?>>
                    <?= $group->name; ?> (с <?= $group->startDateObject->format('d.m.Y') . ($group->endDateObject ? "по {$group->endDateObject->format('d.m.Y')}" : ''); ?>) <?=$group->priceMonth; ?> за месяц
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="group_date_from">Начало занятий</label>
        <?= DatePicker::widget(array_merge(
                DefaultValuesComponent::getDatePickerSettings(),
                [
                    'name' => 'group[date]',
                    'value' => array_key_exists('date', $groupData) ? $groupData['date'] : date('d.m.Y'),
                    'options' => ['id' => 'group_date_from', 'required' => true],
                ]
        ));?>
    </div>

    <button class="btn btn-primary">добавить</button>

    <?php ActiveForm::end(); ?>
</div>
