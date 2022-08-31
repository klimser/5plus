<?php

use \common\models\User;
use yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use \yii\jui\DatePicker;
use \common\components\DefaultValuesComponent;

/* @var $this yii\web\View */
/* @var $groupPupil \common\models\CourseStudent */
/* @var $groupList \common\models\Course[] */

$this->registerJs(<<<SCRIPT
    GroupMove.init();
SCRIPT
);

$this->title = 'Перевести студента в другую группу';
$this->params['breadcrumbs'][] = $this->title;

?>

<h1><?= Html::encode($this->title) ?></h1>

<div id="messages_place"></div>
<form id="move-pupil-form" onsubmit="CourseMove.movePupil(); return false;">
    <div class="form-group">
        <label for="pupil">Студент</label>
        <?php if ($groupPupil): ?>
            <input type="hidden" id="group-move-id" value="<?= $groupPupil->id; ?>">
            <input readonly class="form-control-plaintext" value="<?= $groupPupil->user->name; ?>">
        <?php else: ?>
            <div>
                <input type="hidden" id="group-move-id">
                <input type="hidden" class="autocomplete-user-id" id="pupil-id" onchange="CourseMove.loadCourses();">
                <input class="autocomplete-user form-control" id="pupil-to-move" placeholder="начните печатать фамилию или имя" data-role="<?= User::ROLE_STUDENT; ?>" required>
            </div>
        <?php endif; ?>
    </div>
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label for="group_from">Из группы</label>
                <?php if ($groupPupil): ?>
                    <input type="hidden" id="group_from" value="<?= $groupPupil->group_id; ?>">
                    <input readonly class="form-control-plaintext" value="<?= $groupPupil->group->name; ?>">
                <?php else: ?>
                    <select id="group_from" class="form-control" onchange="CourseMove.selectGroup(this);" required></select>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="move_date">Последний день в старой группе</label>
                <?= DatePicker::widget(ArrayHelper::merge(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'name' => 'date_from',
                        'value' => date('d.m.Y'),
                        'options' => ['id' => 'group-move-date-from', 'required' => true],
                    ]));?>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label for="group_to">В группу</label>
                <select id="group_to" class="form-control" onchange="CourseMove.setGroupToDateInterval(this);" required></select>
            </div>
            <div class="form-group">
                <label for="move_date">Первый день в новой группе</label>
                <?= DatePicker::widget(ArrayHelper::merge(
                    DefaultValuesComponent::getDatePickerSettings(),
                    [
                        'name' => 'date_to',
                        'value' => date('d.m.Y'),
                        'options' => ['id' => 'group-move-date-to', 'required' => true],
                    ]));?>
            </div>
        </div>
    </div>

    <button class="btn btn-primary" id="move_pupil_button">Перевести</button>
</form>
