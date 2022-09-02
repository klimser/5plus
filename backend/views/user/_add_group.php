<?php

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
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" href="#consultation-tab" aria-controls="consultation-tab" role="tab" data-toggle="tab">консультация</a></li>
    <li class="nav-item"><a class="nav-link" href="#welcome_lesson-tab" aria-controls="welcome_lesson-tab" role="tab" data-toggle="tab">пробный урок</a></li>
    <li class="nav-item"><a class="nav-link" href="#group-tab" aria-controls="group-tab" role="tab" data-toggle="tab">в группу</a></li>
</ul>

<div class="tab-content">
    <div role="tabpanel" class="tab-pane fade active show" id="consultation-tab">
        <div class="consultations mt-2"></div>
        <button type="button" class="btn btn-success" onclick="User.addConsultation();"><span class="fas fa-plus"></span> добавить</button>
    </div>
    <div role="tabpanel" class="tab-pane fade" id="welcome_lesson-tab">
        <div class="welcome_lessons"></div>
        <button type="button" class="btn btn-success" onclick="User.addWelcomeLesson();"><span class="fas fa-plus"></span> добавить</button>
    </div>
    <div role="tabpanel" class="tab-pane fade" id="group-tab">
        <div class="groups"></div>
        <button type="button" class="btn btn-success" onclick="User.addCourse();"><span class="fas fa-plus"></span> добавить</button>
    </div>
</div>
