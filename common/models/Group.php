<?php

namespace common\models;

use common\components\GroupComponent;
use backend\models\Event;
use common\components\extended\ActiveRecord;

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
 * @property string[] $scheduleData
 * @property string $weekday
 * @property int $lesson_price
 * @property int $lesson_price_discount
 * @property int $price3Month
 * @property double $teacher_rate
 * @property string $room_number
 * @property int $active
 * @property string $date_start
 * @property string $date_end
 * @property string $date_charge_till
 * @property \DateTime $startDateObject
 * @property \DateTime $endDateObject
 * @property \DateTime $chargeDateObject
 *
 * @property User[] $pupils
 * @property GroupPupil[] $groupPupils
 * @property GroupPupil[] $activeGroupPupils
 * @property GroupPupil[] $inactiveGroupPupils
 * @property Subject $subject
 * @property Teacher $teacher
 * @property GroupType $type
 * @property Event[] $events
 * @property Event[] $eventsByDateMap
 */
class Group extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%group}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'subject_id', 'type_id', 'teacher_id', 'lesson_price', 'date_start'], 'required'],
            [['subject_id', 'teacher_id', 'type_id', 'lesson_price', 'lesson_price_discount', 'active'], 'integer'],
            [['teacher_rate'], 'number', 'min'=> 0, 'max' => 100],
            [['name', 'legal_name'], 'string', 'max' => 50],
            [['schedule'], 'string', 'max' => 255],
            [['weekday'], 'string', 'max' => 6],
            [['room_number'], 'string', 'max' => 25],
            [['date_start', 'date_end'], 'date', 'format' => 'yyyy-MM-dd'],
            [['date_start', 'date_end'], 'safe'],
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
            'legal_name' => 'Официальное название (для договора)',
            'subject_id' => 'Предмет',
            'teacher_id' => 'Учитель',
            'type_id' => 'Тип группы',
            'schedule' => 'График занятий группы',
            'weekday' => 'В какие дни недели занятия',
            'lesson_price' => 'Стоимость ОДНОГО занятия',
            'lesson_price_discount' => 'Стоимость ОДНОГО занятия со скидкой',
            'teacher_rate' => 'Процент начислений зарплаты учителю',
            'room_number' => 'Номер кабинета',
            'active' => 'Занимается',
            'date_start' => 'Дата начала занятий',
            'date_end' => 'Дата завершения занятий',
            'date_charge_till' => 'Стоимость списана до этой даты',
        ];
    }

    public function getScheduleData()
    {
        return json_decode($this->getAttribute('schedule'), true);
    }
    
    public function setScheduleData($value)
    {
        $this->setAttribute('schedule', json_encode($value));
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(GroupType::class, ['id' => 'type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubject()
    {
        return $this->hasOne(Subject::class, ['id' => 'subject_id']);
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
     * @param \DateTime $eventDate
     * @return Event|null
     */
    public function hasEvent(\DateTime $eventDate): ?Event
    {
        return array_key_exists($eventDate->format('Y-m-d'), $this->eventsByDateMap)
            ? $this->eventsByDateMap[$eventDate->format('Y-m-d')]
            : null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupPupils()
    {
        return $this->hasMany(GroupPupil::class, ['group_id' => 'id'])->with('user')->inverseOf('group');
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
    public function getInactiveGroupPupils()
    {
        $inactiveGroupPupils = [];
        foreach ($this->groupPupils as $groupPupil) {
            if ($groupPupil->active == GroupPupil::STATUS_INACTIVE) $inactiveGroupPupils[] = $groupPupil;
        }
        return $inactiveGroupPupils;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPupils()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('groupPupils');
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDateObject()
    {
        return empty($this->date_start) ? null : new \DateTime($this->date_start . ' midnight');
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDateObject()
    {
        return empty($this->date_end) ? null : new \DateTime($this->date_end . ' midnight');
    }

    /**
     * @return \DateTime|null
     */
    public function getChargeDateObject()
    {
        return empty($this->date_charge_till) ? null : new \DateTime($this->date_charge_till);
    }

    public function getPrice3Month()
    {
        return $this->lesson_price_discount * GroupComponent::getTotalClasses($this->weekday) * 3;
    }
}
