<?php

namespace common\models;

use backend\models\Event;
use common\components\extended\ActiveRecord;
use common\models\traits\GroupParam as GroupParamTrait;
use DateTime;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%group}}".
 *
 * @property int $id
 * @property string $name
 * @property string $legal_name
 * @property int $type_id Тип группы
 * @property int $subject_id
 * @property int $teacher_id
 * @property string $schedule
 * @property int $lesson_price
 * @property int $lesson_price_discount
 * @property int $lesson_duration
 * @property double $teacher_rate
 * @property string $room_number
 * @property int $active
 * @property string $date_start
 * @property string $date_end
 * @property DateTime $startDateObject
 * @property DateTime $endDateObject
 *
 * @property User[] $pupils
 * @property GroupPupil[] $groupPupils
 * @property GroupPupil[] $activeGroupPupils
 * @property GroupPupil[] $finishedGroupPupils
 * @property GroupPupil[] $movedGroupPupils
 * @property Subject $subject
 * @property Teacher $teacher
 * @property GroupType $type
 * @property Event[] $events
 * @property Event[] $eventsByDateMap
 */
class Group extends ActiveRecord
{
    use GroupParamTrait;
    
    public const SCENARIO_EMPTY = 'empty';
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%group}}';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_EMPTY => ['name', 'legal_name', 'subject_id', 'type_id', 'teacher_id', 'lesson_price', 'date_start', 'room_number',
                'lesson_price_discount', 'lesson_duration', 'teacher_rate', 'date_end'],
            self::SCENARIO_DEFAULT => ['name', 'legal_name', 'subject_id', 'type_id', 'teacher_id', 'lesson_price', 'room_number',
                'lesson_price_discount', 'lesson_duration', 'teacher_rate', 'date_end'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'legal_name', 'room_number'], 'trim'],
            [['name', 'legal_name', 'subject_id', 'type_id', 'teacher_id', 'lesson_price', 'date_start'], 'required'],
            [['subject_id', 'teacher_id', 'type_id', 'lesson_price', 'lesson_price_discount', 'lesson_duration', 'active'], 'integer'],
            [['teacher_rate'], 'number', 'min'=> 0, 'max' => 100],
            [['name', 'legal_name'], 'string', 'min' => 3, 'max' => 50],
            [['schedule'], 'string', 'max' => 255],
            [['room_number'], 'string', 'max' => 25],
            [['date_start', 'date_end'], 'date', 'format' => 'yyyy-MM-dd'],
            [['date_start'], 'safe', 'on' => self::SCENARIO_EMPTY],
            [['date_end'], 'safe'],
            [['subject_id'], 'exist', 'targetRelation' => 'subject'],
            [['teacher_id'], 'exist', 'targetRelation' => 'teacher'],
            [['type_id'], 'exist', 'targetRelation' => 'type'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID группы',
            'name' => 'Название группы',
            'legal_name' => 'Официальное название (договор, сайт, бот)',
            'subject_id' => 'Предмет',
            'teacher_id' => 'Учитель',
            'type_id' => 'Тип группы',
            'schedule' => 'График занятий группы',
            'lesson_price' => 'Стоимость ОДНОГО занятия',
            'lesson_price_discount' => 'Стоимость ОДНОГО занятия со скидкой',
            'lesson_duration' => 'прододжительность занятия',
            'teacher_rate' => 'Процент начислений зарплаты учителю',
            'room_number' => 'Номер кабинета',
            'active' => 'Занимается',
            'date_start' => 'Дата начала занятий',
            'date_end' => 'Дата завершения занятий',
            'date_charge_till' => 'Стоимость списана до этой даты',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(GroupType::class, ['id' => 'type_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getSubject()
    {
        return $this->hasOne(Subject::class, ['id' => 'subject_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(Teacher::class, ['id' => 'teacher_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(Event::class, ['group_id' => 'id'])->inverseOf('group');
    }

    /**
     * @return Event[]
     */
    public function getEventsByDateMap()
    {
        $eventMap = [];
        foreach ($this->events as $event) {
            $eventMap[$event->eventDateTime->format('Y-m-d')] = $event;
        }
        return $eventMap;
    }

    /**
     * @param DateTime $eventDate
     * @return Event|null
     */
    public function hasEvent(DateTime $eventDate): ?Event
    {
        return array_key_exists($eventDate->format('Y-m-d'), $this->eventsByDateMap)
            ? $this->eventsByDateMap[$eventDate->format('Y-m-d')]
            : null;
    }

    /**
     * @return ActiveQuery
     */
    public function getGroupPupils()
    {
        return $this->hasMany(GroupPupil::class, ['group_id' => 'id'])
            ->joinWith('user')
            ->orderBy(User::tableName() . '.name')
            ->inverseOf('group');
    }

    /**
     * @return GroupPupil[]
     */
    public function getActiveGroupPupils()
    {
        $activeGroupPupils = [];
        foreach ($this->groupPupils as $groupPupil) {
            if ($groupPupil->active == GroupPupil::STATUS_ACTIVE) $activeGroupPupils[] = $groupPupil;
        }
        return $activeGroupPupils;
    }

    /**
     * @return GroupPupil[]
     */
    public function getFinishedGroupPupils()
    {
        $finishedGroupPupils = [];
        foreach ($this->groupPupils as $groupPupil) {
            if ($groupPupil->active == GroupPupil::STATUS_INACTIVE && $groupPupil->moved == GroupPupil::STATUS_INACTIVE) {
                $finishedGroupPupils[] = $groupPupil;
            }
        }
        return $finishedGroupPupils;
    }

    /**
     * @return GroupPupil[]
     */
    public function getMovedGroupPupils()
    {
        $movedGroupPupils = [];
        foreach ($this->groupPupils as $groupPupil) {
            if ($groupPupil->active == GroupPupil::STATUS_INACTIVE && $groupPupil->moved == GroupPupil::STATUS_ACTIVE) {
                $movedGroupPupils[] = $groupPupil;
            }
        }
        return $movedGroupPupils;
    }

    /**
     * @return ActiveQuery
     */
    public function getPupils()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('groupPupils');
    }
    
    public function setStartDateObject(?DateTime $startDate): void
    {
        $this->date_start = $startDate ? $startDate->format('Y-m-d') : null;
    }

    /**
     * @return DateTime|null
     */
    public function getStartDateObject(): ?DateTime
    {
        return empty($this->date_start) ? null : new DateTime($this->date_start . ' midnight');
    }

    public function setEndDateObject(?DateTime $endDate): void
    {
        $this->date_end = $endDate ? $endDate->format('Y-m-d') : null;
    }
    
    /**
     * @return DateTime|null
     */
    public function getEndDateObject(): ?DateTime
    {
        return empty($this->date_end) ? null : new DateTime($this->date_end . ' midnight');
    }

    /**
     * @return bool
     */
    public function isKids(): bool
    {
        return preg_match('#kids#i', $this->name) || preg_match('#кидс#iu', $this->name);
    }
}
