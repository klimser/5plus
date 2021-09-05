<?php

/* @var $this yii\web\View */
/* @var $pupilLimitDate \DateTime */
/* @var $incomeAllowed bool */
/* @var $contractAllowed bool */

$this->title = 'Панель управления';

$initialJs = "
    User.contractAllowed = " . ($contractAllowed ? 'true' : 'false') . ";
    User.incomeAllowed = " . ($incomeAllowed ? 'true' : 'false') . ";
    Main.loadGroups(false);
"; 
if ($pupilLimitDate !== null) {
    $initialJs .= "Dashboard.pupilLimitDate = '{$pupilLimitDate->format('Y-m-d')}';";
}
$this->registerJs($initialJs);

?>
<div id="messages_place"></div>
<form id="search-form" onsubmit="Dashboard.find(this); return false;">
    <div class="form-group input-group input-group-lg">
        <input class="form-control search" placeholder="Телефон, ФИО и т п" minlength="3" autocomplete="off" required autofocus>
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" onclick="Dashboard.clearInput(this);"><span class="fas fa-times"></span></button>
            <button class="btn btn-success"><span class="fas fa-search"></span></button>
        </div>
    </div>
</form>
<div id="result"></div>

<?= $this->render('_contract'); ?>
<?= $this->render('_income'); ?>
<?= $this->render('_age_confirmation'); ?>
<?= $this->render('_debt'); ?>
<?= $this->render('/welcome-lesson/_modal'); ?>
<?= $this->render('_group_move'); ?>
<?= $this->render('_money_move'); ?>
<?= $this->render('_new_contract'); ?>
<?= $this->render('_end_pupil'); ?>
<?= $this->render('_create_pupil'); ?>
