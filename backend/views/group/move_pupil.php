<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $userId int */
/* @var $groupId int */
/* @var $groupList \backend\models\Group[] */

$this->registerJs(<<<SCRIPT
    Group.loadPupils();
SCRIPT
);

$this->title = 'Перевести студента в другую группу';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row move-pupil">
    <h1><?= Html::encode($this->title) ?></h1>

    <div id="messages_place"></div>
    <div class="row">
        <div class="col-xs-12">
            <div class="form-group">
                <label for="pupil">Студент</label>
                <select id="pupil" class="form-control" data-pupil="<?= $userId; ?>" onchange="Group.loadGroups();"></select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                <label for="group_from">Из группы</label>
                <select id="group_from" class="form-control" data-group="<?= $groupId; ?>" onchange="Group.setMoveDateInterval(this);"></select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                <label for="group_to">В группу</label>
                <select id="group_to" class="form-control">
                    <?php foreach ($groupList as $group): ?>
                        <option value="<?= $group->id; ?>"><?= $group->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="form-group">
                <label for="move_date">Дата перехода (первый день в новой группе)</label>
                <?= \dosamigos\datepicker\DatePicker::widget([
                    'name' => 'move_date',
                    'value' => date('d.m.Y'),
                    'options' => ['id' => 'move_date'],
                    'clientOptions' => [
                        'autoclose' => true,
                        'format' => 'dd.mm.yyyy',
                        'language' => 'ru',
                        'weekStart' => 1,
                    ]
                ]);?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <button class="btn btn-primary" id="move_pupil_button" onclick="Group.movePupil(); return false;">Перевести</button>
        </div>
    </div>
</div>