<?php

use backend\components\DefaultValuesComponent;
use dosamigos\datepicker\DatePicker;
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $requestData array */
/* @var $groups \common\models\Group[] */
/* @var $subjects \common\models\Subject[] */
/* @var $groupData array */
/* @var $paymentData array */
/* @var $contractData array */
/* @var $consultationData array */
/* @var $welcomeLessonData array */
/* @var $pupilLimitDate DateTime|null */
/* @var $companies \common\models\Company[] */
/* @var $amount int */
/* @var $companyId int */
/* @var $incomeAllowed bool */
/* @var $contractAllowed bool */

/** @var bool $addGroup */
$addGroup = array_key_exists('add', $groupData) && $groupData['add'];
$addPayment = array_key_exists('add', $paymentData) && $paymentData['add'];
$addContract = array_key_exists('add', $contractData) && $contractData['add'];
$addWelcomeLesson = array_key_exists('add', $welcomeLessonData) && $welcomeLessonData['add'];

$getGroupOptionsList = function(int $selectedValue) use ($groups): string {
    $list = '';
    foreach ($groups as $group) {
        $list .= "<option value=\"{$group->id}\" " . ($selectedValue == $group->id ? 'selected' : '')
            . " data-price=\"{$group->priceMonth}\" data-price3=\"{$group->price3Month}\">{$group->name} (с {$group->startDateObject->format('d.m.Y')}"
            . ($group->endDateObject ? "по {$group->endDateObject->format('d.m.Y')}" : '')
            . ")</option>";
    }
    return $list;
};


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
    <li role="presentation"><a href="#group-tab" aria-controls="group-tab" role="tab" data-toggle="tab">добавить в группу</a></li>
</ul>

<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="consultation-tab">
        <div id="consultation-mandatory"></div>
        <div id="consultation-optional"></div>
        <button type="button" class="btn btn-success" onclick="User.addConsultation();"><span class="fas fa-plus"></span> добавить</button>
    </div>
    <div role="tabpanel" class="tab-pane" id="welcome_lesson-tab">
        <div id="welcome_lessons"></div>
        <button type="button" class="btn btn-success" onclick="User.addWelcomeLesson();"><span class="fas fa-plus"></span> добавить</button>
    </div>
    <div role="tabpanel" class="tab-pane" id="group-tab">
        <div id="groups"></div>
        <button type="button" class="btn btn-success" onclick="User.addGroup();"><span class="fas fa-plus"></span> добавить</button>
    </div>
</div>
