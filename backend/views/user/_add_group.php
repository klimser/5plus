<?php

use common\components\DefaultValuesComponent;
use dosamigos\datepicker\DatePicker;

/* @var $this \yii\web\View */
/* @var $consultationData array */
/* @var $welcomeLessonData array */
/* @var $groupData array */
/* @var $pupilLimitDate DateTime|null */
/* @var $incomeAllowed bool */
/* @var $contractAllowed bool */

$initialJs = 'User.contractAllowed = ' . ($contractAllowed ? 'true' : 'false') . ";\n";
$initialJs .= 'User.incomeAllowed = ' . ($incomeAllowed ? 'true' : 'false') . ";\n";
if ($pupilLimitDate !== null) {
    $initialJs .= "User.pupilLimitDate = '{$pupilLimitDate->format('Y-m-d')}';\n";
}
// Consultations
foreach ($consultationData as $consultationSubjectId) {
    $initialJs .= "User.consultationList.push({$consultationSubjectId});\n";
}

// Welcome lessons
foreach ($welcomeLessonData as $welcomeLesson) {
    $initialJs .= "User.welcomeLessonList.push(" . json_encode($welcomeLesson) . ");\n";
}

// Groups
foreach ($groupData as $group) {
    $initialJs .= "User.groupList.push(" . json_encode($group) . ");\n";
}

$initialJs .= "User.init();\n";
$this->registerJs($initialJs);
?>
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#consultation-tab" aria-controls="consultation-tab" role="tab" data-toggle="tab">консультация</a></li>
    <li role="presentation"><a href="#welcome_lesson-tab" aria-controls="welcome_lesson-tab" role="tab" data-toggle="tab">пробный урок</a></li>
    <li role="presentation"><a href="#group-tab" aria-controls="group-tab" role="tab" data-toggle="tab">в группу</a></li>
</ul>

<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="consultation-tab">
        <div class="consultation-mandatory m-t-10"></div>
        <div class="consultation-optional m-t-10"></div>
        <button type="button" class="btn btn-success" onclick="User.addConsultation();"><span class="fas fa-plus"></span> добавить</button>
    </div>
    <div role="tabpanel" class="tab-pane" id="welcome_lesson-tab">
        <div class="welcome_lessons m-t-10"></div>
        <button type="button" class="btn btn-success" onclick="User.addWelcomeLesson();"><span class="fas fa-plus"></span> добавить</button>
    </div>
    <div role="tabpanel" class="tab-pane" id="group-tab">
        <div class="groups m-t-10"></div>
        <button type="button" class="btn btn-success" onclick="User.addGroup();"><span class="fas fa-plus"></span> добавить</button>
    </div>
</div>
