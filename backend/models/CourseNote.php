<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\Course;
use common\models\Teacher;
use common\models\traits\Inserted;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "course_note".
 *
 * @property int     $id
 * @property int     $course_id
 * @property int     $teacher_id
 * @property string  $topic
 * @property string  $created_at
 *
 * @property Course  $course
 * @property Teacher $teacher
 */
class CourseNote extends ActiveRecord
{
    use Inserted;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%course_note}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['course_id', 'teacher_id', 'topic'], 'required'],
            [['course_id', 'teacher_id'], 'integer'],
            [['topic'], 'string', 'max' => 255],
            [['course_id'], 'exist', 'targetRelation' => 'course'],
            [['teacher_id'], 'exist', 'targetRelation' => 'teacher'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'course_id' => 'Group ID',
            'teacher_id' => 'Teacher ID',
            'topic' => 'Topic',
            'created_at' => 'Created At',
        ];
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }

    public function getTeacher(): ActiveQuery
    {
        return $this->hasOne(Teacher::class, ['id' => 'teacher_id']);
    }
}
