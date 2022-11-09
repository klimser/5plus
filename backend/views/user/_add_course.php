<?php

/* @var $this \yii\web\View */
/* @var $consultationData array */
/* @var $welcomeLessonData array */
/* @var $courseData array */
/* @var $studentLimitDate DateTime|null */

$initialJs = '';

if ($studentLimitDate !== null) {
    $initialJs .= "User.studentLimitDate = '{$studentLimitDate->format('Y-m-d')}';\n";
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
foreach ($courseData as $course) {
    $initialJs .= "User.courseList.push(" . json_encode($course) . ");\n";
}

$initialJs .= "User.init();\n";
$this->registerJs($initialJs);
?>
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" href="#consultation-tab" aria-controls="consultation-tab" role="tab" data-toggle="tab">консультация</a></li>
    <li class="nav-item"><a class="nav-link" href="#welcome_lesson-tab" aria-controls="welcome_lesson-tab" role="tab" data-toggle="tab">пробный урок</a></li>
    <li class="nav-item"><a class="nav-link" href="#course-tab" aria-controls="course-tab" role="tab" data-toggle="tab">в группу</a></li>
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
    <div role="tabpanel" class="tab-pane fade" id="course-tab">
        <div class="courses"></div>
        <button type="button" class="btn btn-success" onclick="User.addCourse();"><span class="fas fa-plus"></span> добавить</button>
    </div>
</div>
