<?php

namespace backend\models;

use common\components\ComponentContainer;
use common\components\extended\ActiveRecord;
use common\components\MoneyComponent;
use common\models\Course;
use common\models\CourseConfig;
use common\models\CourseStudent;
use common\models\Teacher;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%event}}".
 *
 * @property int               $id
 * @property int               $course_id
 * @property string            $event_date
 * @property int               $status
 * @property-read string       $eventTime
 * @property-read  int         $limitAttendTimestamp
 * @property DateTimeImmutable $eventDateTime
 * @property-read \DateTime    $teacherEditLimitDate
 *
 * @property Course            $course
 * @property CourseConfig      $courseConfig
 * @property EventMember[]     $members
 * @property EventMember[]     $membersWithPayments
 * @property WelcomeLesson[]   $welcomeMembers
 * @property-read Teacher      $teacher
 */
class Event extends ActiveRecord
{
    const STATUS_UNKNOWN  = 0;
    const STATUS_PASSED   = 1;
    const STATUS_CANCELED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%event}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['course_id', 'status'], 'integer'],
            [['course_id', 'event_date'], 'required'],
            [['event_date'], 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            [['status'], 'in', 'range' => [self::STATUS_UNKNOWN, self::STATUS_PASSED, self::STATUS_CANCELED]],
            [['course_id'], 'exist', 'targetRelation' => 'course'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_date' => 'Дата',
            'course' => 'Группа',
            'status' => 'Статус занятия',
        ];
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }

    public function getCourseConfig(): ActiveQuery
    {
        return $this->hasOne(CourseConfig::class, ['course_id' => 'course_id'])
            ->andWhere(['and', 'date_from <= :event_date', ['or', 'date_to IS NULL', 'date_to > :event_date']], ['event_date' => $this->event_date]);
    }

    public function getMembers(): ActiveQuery
    {
        return $this->hasMany(EventMember::class, ['event_id' => 'id'])->inverseOf('event');
    }

    public function getMembersWithPayments(): ActiveQuery
    {
        return $this->hasMany(EventMember::class, ['event_id' => 'id'])->with('payments')->inverseOf('event');
    }

    public function getTeacher(): Teacher
    {
        return CourseConfig::findByDate($this->course, $this->eventDateTime)->teacher;
    }

    public function getEventDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->event_date);
    }

    public function getEventTime()
    {
        return $this->getEventDateTime()->format('H:i');
    }

    public function getLimitAttendTimestamp(): int
    {
        return $this->eventDateTime->modify('+1 hour')->getTimestamp();
    }

    public function getTeacherEditLimitDate(): DateTimeImmutable
    {
        $lessonDuration = CourseConfig::findByDate($this->course, $this->eventDateTime)->lesson_duration;

        return $this->eventDateTime->modify("+{$lessonDuration} minutes")->modify('+1 hour');
    }

    public function findByCourseStudent(CourseStudent $courseStudent): ?EventMember
    {
        foreach ($this->members as $eventMember) {
            if ($eventMember->course_student_id === $courseStudent->id) {
                return $eventMember;
            }
        }

        return null;
    }

    public function addCourseStudent(CourseStudent $courseStudent): ?EventMember
    {
        if (!$eventMember = $this->findByCourseStudent($courseStudent)) {
            $eventMember = new EventMember();
            $eventMember->event_id = $this->id;
            $eventMember->course_student_id = $courseStudent->id;
            if (self::STATUS_CANCELED === $this->status) {
                $eventMember->status = EventMember::STATUS_MISS;
            }
            if (!$eventMember->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('Event.addCourseStudent', $eventMember->getErrorsAsString(), true);
                throw new Exception('Server error');
            }
            $this->link('members', $eventMember);
        }

        return $eventMember;
    }

    public function removeCourseStudent(CourseStudent $courseStudent): void
    {
        if ($eventMember = $this->findByCourseStudent($courseStudent)) {
            $this->unlink('members', $eventMember, true);
        }
    }

    public function beforeDelete()
    {
        foreach ($this->membersWithPayments as $eventMember) {
            foreach ($eventMember->payments as $payment) {
                MoneyComponent::cancelPayment($payment);
            }

            $this->unlink('members', $eventMember, true);
        }

        return parent::beforeDelete();
    }

    public static function findByCourseAndDate(Course $course, DateTimeInterface $date): ?self
    {
        /** @var Event|null $event */
        $event = Event::find()
            ->andWhere(['course_id' => $course->id])
            ->andWhere(['between', 'event_date', $date->format('Y-m-d') . ' 00:00:00', $date->format('Y-m-d') . ' 23:59:59'])
            ->one();

        return $event;
    }

    public function getWelcomeMembers(): ActiveQuery
    {
        return $this->hasMany(WelcomeLesson::class, ['course_id' => 'course_id', 'lesson_date' => 'event_date']);
    }
}
