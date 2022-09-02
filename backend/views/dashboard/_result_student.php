<?php
/** @var $student \common\models\User */
?>

<div class="card result-student">
    <div class="card-header bg-success text-white border-top border-white justify-content-between align-items-center d-flex px-3 py-2">
        <div class="font-weight-bold">
            <span class="badge badge-secondary">студент</span>
            <span class="student-name"><?= $student->name; ?></span>
        </div>
        <button class="btn btn-light" onclick="Dashboard.toggleStudentInfo(this);">
            <span class="fas fa-arrow-alt-circle-down"></span>
        </button>
    </div>
    <div class="card-body collapse student-info p-3" data-id="<?= $student->id; ?>"></div>
</div>
