<?php

namespace common\models;

use backend\models\Event;
use backend\models\EventMember;
use common\components\extended\ActiveRecord;
use DateTimeImmutable;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%course_student}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property int $active
 * @property string $date_start
 * @property string $date_end
 * @property int $moved
 * @property int            $end_reason
 * @property string         $comment
 * @property string         $date_charge_till
 * @property int            $paid_lessons
 * @property \DateTime      $startDateObject
 * @property \DateTime|null $endDateObject
 * @property \DateTime      $chargeDateObject
 * @property int            $moneyLeft
 * @property Payment[]      $payments
 * @property User           $user
 * @property Course         $course
 * @property EventMember[]  $eventMembers
 */
class CourseStudent extends ActiveRecord
{
    public const END_REASON_FINISH = 1;
    public const END_REASON_TEACHER = 2;
    public const END_REASON_LEVEL_TOO_LOW = 3;
    public const END_REASON_LEVEL_TOO_HIGH = 4;
    public const END_REASON_OTHER_GROUP = 5;
    public const END_REASON_TOO_CROWDED = 6;
    public const END_REASON_SUBJECT = 7;
    public const END_REASON_OTHER = 8;
    public const END_REASON_VACATION = 9;
    
    public const END_REASON_LIST = [
        self::END_REASON_FINISH,
        self::END_REASON_TEACHER,
        self::END_REASON_LEVEL_TOO_LOW,
        self::END_REASON_LEVEL_TOO_HIGH,
        self::END_REASON_OTHER_GROUP,
        self::END_REASON_TOO_CROWDED,
        self::END_REASON_SUBJECT,
        self::END_REASON_VACATION,
        self::END_REASON_OTHER,
    ];

    public const END_REASON_LABELS = [
        self::END_REASON_FINISH => 'курс завершился',
        self::END_REASON_TEACHER => 'не понравился учитель',
        self::END_REASON_LEVEL_TOO_LOW => 'нужен уровень выше',
        self::END_REASON_LEVEL_TOO_HIGH => 'нужен уровень ниже',
        self::END_REASON_OTHER_GROUP => 'придет в другую группу',
        self::END_REASON_TOO_CROWDED => 'слишком большая группа',
        self::END_REASON_SUBJECT => 'не нужен предмет для поступления',
        self::END_REASON_VACATION => 'каникулы',
        self::END_REASON_OTHER => 'другое',
    ];

    public static function tableName()
    {
        return '{{%course_student}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'course_id', 'date_start'], 'required'],
            [['user_id', 'course_id', 'active', 'moved', 'end_reason', 'paid_lessons'], 'integer'],
            [['date_start', 'date_end'], 'date', 'format' => 'yyyy-MM-dd'],
            [['comment'], 'string'],
            [['user_id'], 'exist', 'targetRelation' => 'user', 'filter' => ['role' => User::ROLE_STUDENT]],
            [['course_id'], 'exist', 'targetRelation' => 'course'],
            [['active', 'moved'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['active'], 'default', 'value' => self::STATUS_ACTIVE],
            [['moved'], 'default', 'value' => self::STATUS_INACTIVE],
            ['end_reason', 'in', 'range' => self::END_REASON_LIST],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID записи',
            'user_id' => 'ID ученика',
            'course_id' => 'ID группы',
            'active' => 'Активен ли студент',
            'moved' => 'Студент перешел в другую группу',
            'end_reason' => 'Причина ухода',
            'comment' => 'Комментарий',
            'date_start' => 'Дата начала занятий в группе',
            'date_end' => 'Дата завершения занятий в группе',
            'date_charge_till' => 'Стоимость списана до этой даты',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id'])->inverseOf('courseStudents');
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id'])->inverseOf('courseStudents');
    }

    public function getPayments(): ActiveQuery
    {
        return $this->hasMany(Payment::class, ['course_student_id' => 'id'])->orderBy([Payment::tableName() . '.created_at' => SORT_ASC]);
    }

    public function getEventMembers(): ActiveQuery
    {
        return $this->hasMany(EventMember::class, ['course_student_id' => 'id'])
            ->joinWith('event')
            ->orderBy([Event::tableName() . '.event_date' => SORT_ASC])
            ->inverseOf('courseStudent');
    }

    public function getStartDateObject(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->date_start . ' midnight');
    }

    public function getEndDateObject(): ?DateTimeImmutable
    {
        if (!empty($this->date_end)) return new DateTimeImmutable($this->date_end . ' midnight');
        if ($this->course->active == Course::STATUS_INACTIVE && $this->course->date_end) return $this->course->endDateObject;
        return null;
    }

    public function getChargeDateObject(): ?DateTimeImmutable
    {
        return empty($this->date_charge_till) ? null : new DateTimeImmutable($this->date_charge_till);
    }

    public function getMoneyLeft(): int
    {
        return Payment::find()
            ->andWhere(['user_id' => $this->user_id, 'course_id' => $this->course_id])
            ->select('SUM(amount)')
            ->scalar() ?? 0;
    }
}
