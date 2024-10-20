<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\GroupPupil;
use common\models\Payment;
use yii;

/**
 * This is the model class for table "{{%event_member}}".
 *
 * @property int $id
 * @property int $group_pupil_id
 * @property int $event_id
 * @property int $status
 * @property int $mark
 * @property int $mark_homework
 * @property int $attendance_notification_sent
 * @property int $mark_notification_sent
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


    public static function tableName()
    {
        return '{{%event_member}}';
    }


    public function rules()
    {
        return [
            [['group_pupil_id', 'event_id'], 'required'],
            [['group_pupil_id', 'event_id', 'status', 'mark', 'mark_homework'], 'integer'],
            [['event_id'], 'exist', 'targetRelation' => 'event'],
            [['group_pupil_id'], 'exist', 'targetRelation' => 'groupPupil'],
            [['status'], 'in', 'range' => [self::STATUS_UNKNOWN, self::STATUS_ATTEND, self::STATUS_MISS]],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_id' => 'Занятие',
            'status' => 'Статус',
            'mark' => 'Оценка в классе',
            'mark_homework' => 'Оценка ДЗ',
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
