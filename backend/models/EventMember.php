<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\CourseStudent;
use common\models\Payment;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%event_member}}".
 *
 * @property int           $id
 * @property int           $course_student_id
 * @property int           $event_id
 * @property int           $status
 * @property array         $mark
 * @property int           $attendance_notification_sent
 * @property int           $mark_notification_sent
 *
 * @property Event         $event
 * @property CourseStudent $courseStudent
 * @property Payment[]     $payments
 */
class EventMember extends ActiveRecord
{
    public const STATUS_UNKNOWN = 0;
    public const STATUS_ATTEND = 1;
    public const STATUS_MISS = 2;

    public const MARK_LESSON = 'lesson';
    public const MARK_HOMEWORK = 'homework';
    public const MARK_TEST = 'test';

    public static function tableName()
    {
        return '{{%event_member}}';
    }


    public function rules()
    {
        return [
            [['course_student_id', 'event_id'], 'required'],
            [['course_student_id', 'event_id', 'status', 'mark', 'mark_homework'], 'integer'],
            [['event_id'], 'exist', 'targetRelation' => 'event'],
            [['course_student_id'], 'exist', 'targetRelation' => 'courseStudent'],
            [['status'], 'in', 'range' => [self::STATUS_UNKNOWN, self::STATUS_ATTEND, self::STATUS_MISS]],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_id' => 'Занятие',
            'status' => 'Статус',
            'mark' => 'Оценки',
        ];
    }

    public function getEvent(): ActiveQuery
    {
        return $this->hasOne(Event::class, ['id' => 'event_id']);
    }

    public function getCourseStudent(): ActiveQuery
    {
        return $this->hasOne(CourseStudent::class, ['id' => 'course_student_id'])->inverseOf('eventMembers');
    }

    public function getPayments(): ActiveQuery
    {
        return $this->hasMany(Payment::class, ['event_member_id' => 'id'])->inverseOf('eventMember');
    }
}
