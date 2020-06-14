<?php
/** @var $pupil \common\models\User */
?>

<div class="panel panel-success result-pupil">
    <div class="panel-heading">
        <span class="label label-success">студент</span>
        <span class="pupil-name"><?= $pupil->name; ?></span>
        <button class="btn btn-default pull-right panel-info-button" onclick="Dashboard.togglePupilInfo(this);">
            <span class="fas fa-arrow-alt-circle-down"></span>
        </button>
    </div>
    <div class="panel-body hidden pupil-info" data-id="<?= $pupil->id; ?>"></div>
</div>
