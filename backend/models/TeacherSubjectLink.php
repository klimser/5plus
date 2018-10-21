<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\Subject;
use common\models\Teacher;
use yii;

/**
 * This is the model class for table "{{%teacher_subject}}".
 *
 * @property string $id
 * @property int $teacher_id
 * @property int $subject_id
 *
 * @property Teacher $teacher
 * @property Subject $subject
 */
class TeacherSubjectLink extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%teacher_subject}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['teacher_id', 'subject_id'], 'required'],
            [['teacher_id', 'subject_id'], 'integer'],
            [['teacher_id'], 'exist', 'targetRelation' => 'teacher'],
            [['subject_id'], 'exist', 'targetRelation' => 'subject'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'teacher_id' => 'Teacher ID',
            'subject_id' => 'Subject ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(Teacher::class, ['id' => 'teacher_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubject()
    {
        return $this->hasOne(Subject::class, ['id' => 'subject_id']);
    }
}
