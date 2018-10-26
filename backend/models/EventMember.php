<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use yii;

/**
 * This is the model class for table "{{%event_member}}".
 *
 * @property int $id
 * @property int $group_pupil_id
 * @property int $event_id
 * @property int $status
 * @property int $mark
 *
 * @property Event $event
 * @property GroupPupil $groupPupil
 * @property Payment[] $payments
 */
class EventMember extends ActiveRecord
{
    const STATUS_UNKNOWN = 0;
    const STATUS_ATTEND = 1;
    const STATUS_MISS = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%event_member}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['group_pupil_id', 'event_id'], 'required'],
            [['group_pupil_id', 'event_id', 'status', 'mark'], 'integer'],
            [['event_id'], 'exist', 'targetRelation' => 'event'],
            [['group_pupil_id'], 'exist', 'targetRelation' => 'groupPupil'],
            [['status'], 'in', 'range' => [self::STATUS_UNKNOWN, self::STATUS_ATTEND, self::STATUS_MISS]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_id' => 'Занятие',
            'status' => 'Статус',
            'mark' => 'Оценка',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Event::class, ['id' => 'event_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupPupil()
    {
        return $this->hasOne(GroupPupil::class, ['id' => 'group_pupil_id'])->inverseOf('eventMembers');
    }

    /**
     * @return yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(Payment::class, ['event_member_id' => 'id'])->inverseOf('eventMember');
    }
}
