<?php

namespace common\models;

use backend\models\CourseNote;
use backend\models\Event;
use backend\models\WelcomeLesson;
use common\components\CourseComponent;
use common\components\extended\ActiveRecord;
use DateTimeImmutable;
use DateTimeInterface;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%course}}".
 *
 * @property int $id
 * @property int $type_id Тип группы
 * @property int $subject_id
 * @property int $active
 * @property int $category_id
 * @property string            $date_start
 * @property string            $date_end
 * @property DateTimeImmutable $startDateObject
 * @property DateTimeImmutable $endDateObject
 *
 * @property User[]          $students
 * @property CourseStudent[] $courseStudents
 * @property CourseStudent[] $activeCourseStudents
 * @property CourseStudent[] $finishedCourseStudents
 * @property CourseStudent[] $movedCourseStudents
 * @property CourseConfig[]  $courseConfigs
 * @property Subject         $subject
 * @property CourseCategory  $category
 * @property Teacher         $teacher
 * @property Event[]         $events
 * @property Event[]         $eventsByDateMap
 * @property CourseNote[]    $notes
 * @property CourseNote|null $note
 * @property CourseConfig    $courseConfig
 * @property CourseConfig|null  $latestCourseConfig
 */
class Course extends ActiveRecord
{
    public const SCENARIO_EMPTY = 'empty';

    /** @var array<string,int>|null */
    private ?array $eventIdByDateMap = null;

    private CourseConfig $courseConfig;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%course}}';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_EMPTY => ['type_id', 'category_id', 'subject_id', 'date_start', 'date_end'],
            self::SCENARIO_DEFAULT => ['type_id', 'category_id', 'subject_id', 'date_end'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_id', 'subject_id', 'category_id', 'date_start'], 'required'],
            [['type_id', 'subject_id', 'active', 'category_id'], 'integer'],
            [['date_start', 'date_end'], 'date', 'format' => 'yyyy-MM-dd'],
            [['date_start'], 'safe', 'on' => self::SCENARIO_EMPTY],
            [['date_end'], 'safe'],
            [['subject_id'], 'exist', 'targetRelation' => 'subject'],
            [['category_id'], 'exist', 'targetRelation' => 'category'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID группы',
            'type_id' => 'Тип группы',
            'subject_id' => 'Предмет',
            'active' => 'Занимается',
            'category_id' => 'Категория',
            'date_start' => 'Дата начала занятий',
            'date_end' => 'Дата завершения занятий',
            'date_charge_till' => 'Стоимость списана до этой даты',
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->eventIdByDateMap = null;
    }

    public function getType(): ActiveQuery
    {
        return $this->hasOne(CourseType::class, ['id' => 'type_id']);
    }

    public function getSubject(): ActiveQuery
    {
        return $this->hasOne(Subject::class, ['id' => 'subject_id']);
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(CourseCategory::class, ['id' => 'category_id']);
    }

    public function getTeacher(): Teacher
    {
        return $this->latestCourseConfig->teacher;
    }

    public function getEvents(): ActiveQuery
    {
        return $this->hasMany(Event::class, ['course_id' => 'id'])->inverseOf('course');
    }

    /**
     * @return array<string,int>
     */
    public function getEventIdByDateMap(): array
    {
        if (null === $this->eventIdByDateMap) {
            $this->eventIdByDateMap = [];
            if ($this->id) {
                $eventData = Event::find()->select(['id', 'event_date'])
                    ->andWhere(['course_id' => $this->id])
                    ->asArray()
                    ->all();
                $this->eventIdByDateMap = ArrayHelper::map(
                    $eventData,
                    static fn(array $row) => (new DateTimeImmutable($row['event_date']))->format('Y-m-d'),
                    static fn(array $row) => (int) $row['id']
                );
            }
        }

        return $this->eventIdByDateMap;
    }

    public function hasEvent(DateTimeInterface $eventDate): bool
    {
        return array_key_exists($eventDate->format('Y-m-d'), $this->getEventIdByDateMap());
    }

    public function getEventByDate(DateTimeInterface $eventDate): ?Event
    {
        if (!array_key_exists($eventDate->format('Y-m-d'), $this->getEventIdByDateMap())) {
            return null;
        }

        return Event::findOne($this->getEventIdByDateMap()[$eventDate->format('Y-m-d')]);
    }

    public function getLatestCourseConfig(): ?CourseConfig
    {
        return 0 === count($this->courseConfigs) ? null : $this->courseConfigs[count($this->courseConfigs) - 1];
    }

    public function getCourseConfig(): CourseConfig
    {
        if (!isset($this->courseConfig)) {
            $this->courseConfig = CourseComponent::getCourseConfig($this, new DateTimeImmutable());
        }

        return $this->courseConfig;
    }

    public function getCourseConfigByDate(DateTimeInterface $date): CourseConfig
    {
        return CourseComponent::getCourseConfig($this, $date);
    }

    public function getCourseConfigs(): ActiveQuery
    {
        return $this->hasMany(CourseConfig::class, ['course_id' => 'id'])
            ->with('teacher')
            ->orderBy(CourseConfig::tableName() . '.date_from')
            ->inverseOf('course');
    }

    public function getCourseStudents(): ActiveQuery
    {
        return $this->hasMany(CourseStudent::class, ['course_id' => 'id'])
            ->joinWith('user')
            ->orderBy(User::tableName() . '.name')
            ->inverseOf('course');
    }

    /**
     * @return CourseStudent[]
     */
    public function getActiveCourseStudents(): array
    {
        $activeCourseStudents = [];
        foreach ($this->courseStudents as $courseStudent) {
            if ($courseStudent->active == CourseStudent::STATUS_ACTIVE) {
                $activeCourseStudents[] = $courseStudent;
            }
        }

        return $activeCourseStudents;
    }

    /**
     * @return CourseStudent[]
     */
    public function getFinishedCourseStudents(): array
    {
        $finishedCourseStudents = [];
        foreach ($this->courseStudents as $courseStudent) {
            if ($courseStudent->active === CourseStudent::STATUS_INACTIVE && $courseStudent->moved === CourseStudent::STATUS_INACTIVE) {
                $finishedCourseStudents[] = $courseStudent;
            }
        }

        return $finishedCourseStudents;
    }

    /**
     * @return CourseStudent[]
     */
    public function getMovedCourseStudents()
    {
        $movedCourseStudents = [];
        foreach ($this->courseStudents as $courseStudent) {
            if ($courseStudent->active == CourseStudent::STATUS_INACTIVE && $courseStudent->moved == CourseStudent::STATUS_ACTIVE) {
                $movedCourseStudents[] = $courseStudent;
            }
        }

        return $movedCourseStudents;
    }

    public function getStudents(): ActiveQuery
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('courseStudents');
    }

    public function getNotes(): ActiveQuery
    {
        return $this->hasMany(CourseNote::class, ['course_id' => 'id'])
            ->orderBy([CourseNote::tableName() . '.created_at' => SORT_DESC])
            ->inverseOf('course');
    }

    public function getNote(): ?CourseNote
    {
        return 0 < count($this->notes) ? $this->notes[0] : null;
    }

    public function setStartDateObject(?DateTimeInterface $startDate): void
    {
        $this->date_start = $startDate?->format('Y-m-d');
    }

    public function getStartDateObject(): ?DateTimeImmutable
    {
        return empty($this->date_start) ? null : new DateTimeImmutable($this->date_start . ' midnight');
    }

    public function setEndDateObject(?DateTimeInterface $endDate): void
    {
        $this->date_end = $endDate?->format('Y-m-d');
    }

    public function getEndDateObject(): ?DateTimeImmutable
    {
        return empty($this->date_end) ? null : new DateTimeImmutable($this->date_end . ' midnight');
    }

    public function hasWelcomeLessons(DateTimeInterface $date): bool
    {
        $lessonsCount = WelcomeLesson::find()
            ->andWhere(['course_id' => $this->id])
            ->andWhere(['between', 'lesson_date', $date->format('Y-m-d') . ' 00:00:00', $date->format('Y-m-d') . ' 23:59:59'])
            ->count();

        return $lessonsCount > 0;
    }
}
