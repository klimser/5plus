<?php
/** @var $pupil \common\models\User */
?>

<div class="card result-pupil">
    <div class="card-header bg-success text-white border-top border-white justify-content-between align-items-center d-flex px-3 py-2">
        <div class="font-weight-bold">
            <span class="badge badge-secondary">студент</span>
            <span class="pupil-name"><?= $pupil->name; ?></span>
        </div>
        <button class="btn btn-light" onclick="Dashboard.togglePupilInfo(this);">
            <span class="fas fa-arrow-alt-circle-down"></span>
        </button>
    </div>
    <div class="card-body collapse pupil-info p-3" data-id="<?= $pupil->id; ?>"></div>
</div>
