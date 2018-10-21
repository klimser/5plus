<?php

namespace frontend\components\widgets;


use common\models\Subject;
use common\models\Teacher;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

class TeacherSubjectWidget extends Widget
{
    /** @var  Teacher */
    public $teacher;
    public $teacherCount;

    public function init()
    {
        parent::init();
        if (!$this->teacherCount) $this->teacherCount = 4;
    }

    public function run()
    {
        $teachers = Teacher::find()
            ->andWhere(['page_visibility' => Subject::STATUS_ACTIVE])
            ->andWhere(['!=', '{{%teacher}}.id', $this->teacher->id])
            ->joinWith('subjects')
            ->andWhere(['in', '{{%module_subject}}.id', ArrayHelper::getColumn($this->teacher->subjects, 'id')])
            ->orderBy('rand()')->limit($this->teacherCount)->all();
        if (empty($teachers)) return '';

        return $this->render('teacher-subject', ['teachers' => $teachers]);
    }
}