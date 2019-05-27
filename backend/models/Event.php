<?php

namespace backend\models;

use common\components\ComponentContainer;
use common\components\extended\ActiveRecord;
use common\models\Group;
use common\models\GroupParam;
use common\models\GroupPupil;
use common\models\Teacher;
use yii;

/**
 * This is the model class for table "{{%event}}".
 *
 * @property int $id
 * @property int $group_id
 * @property Teacher $teacher
 * @property string $event_date
 * @property int $status
 * @property-read string $eventTime
 * @property-read  int $limitAttendTimestamp
 * @property \DateTime $eventDateTime
 *
 * @property Group $group
 * @property EventMember[] $members
 */
class Event extends ActiveRecord
{
    const STATUS_UNKNOWN = 0;
    const STATUS_PASSED = 1;
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
            [['group_id', 'status'], 'integer'],
            [['event_date'], 'required'],
            [['event_date'], 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            [['status'], 'in', 'range' => [self::STATUS_UNKNOWN, self::STATUS_PASSED, self::STATUS_CANCELED]],
            [['group_id'], 'exist', 'targetRelation' => 'group'],
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
            'group' => 'Группа',
            'status' => 'Статус занятия',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMembers()
    {
        return $this->hasMany(EventMember::class, ['event_id' => 'id'])->inverseOf('event');
    }

    /**
     * @return Teacher
     */
    public function getTeacher()
    {
        $groupParam = GroupParam::findByDate($this->group, $this->eventDateTime);
        if ($groupParam) return $groupParam->teacher;
        return $this->group->teacher;
    }

    /**
     * @return \DateTime
     */
    public function getEventDateTime()
    {
        return new \DateTime($this->event_date);
    }

    public function getEventTime()
    {
        return $this->getEventDateTime()->format('H:i');
    }

    public function getLimitAttendTimestamp(): int
    {
        $limitDate = clone $this->eventDateTime;
        $limitDate->modify('+1 hour');
        return $limitDate->getTimestamp();
    }

    /**
     * @param GroupPupil $groupPupil
     * @return EventMember|null
     */
    public function hasGroupPupil(GroupPupil $groupPupil): ?EventMember
    {
        foreach ($this->members as $eventMember) {
            if ($eventMember->group_pupil_id == $groupPupil->id) return $eventMember;
        }
        return null;
    }

    /**
     * @param GroupPupil $groupPupil
     * @return EventMember|null
     * @throws \Exception
     */
    public function addGroupPupil(GroupPupil $groupPupil)
    {
        $eventMember = $this->hasGroupPupil($groupPupil);
        if (!$eventMember) {
            $eventMember = new EventMember();
            $eventMember->event_id = $this->id;
            $eventMember->group_pupil_id = $groupPupil->id;
            if ($this->status == self::STATUS_CANCELED) $eventMember->status = EventMember::STATUS_MISS;
            if (!$eventMember->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('Event.addGroupPupil', $eventMember->getErrorsAsString(), true);
                throw new \Exception('Server error');
            }
            $this->link('members', $eventMember);
        }
        return $eventMember;
    }

    /**
     * @param GroupPupil $groupPupil
     * @throws \Exception
     */
    public function removeGroupPupil(GroupPupil $groupPupil)
    {
        $eventMember = $this->hasGroupPupil($groupPupil);
        if ($eventMember) {
            $this->unlink('members', $eventMember, true);
        }
    }

    /**
     * @param Group $group
     * @param \DateTime $date
     * @return Event|null
     */
    public static function findByGroupAndDate(Group $group, \DateTime $date): ?self
    {
        /** @var Event|null $event */
        $event = Event::find()
            ->andWhere(['group_id' => $group->id])
            ->andWhere(['between', 'event_date', $date->format('Y-m-d') . ' 00:00:00', $date->format('Y-m-d') . ' 23:59:59'])
            ->one();
        return $event;
    }
}
