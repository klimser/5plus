<?php

namespace backend\models;

use \common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%payment}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int $group_id
 * @property int $admin_id
 * @property int $group_pupil_id
 * @property int $amount
 * @property int $discount Скидочный платёж
 * @property string $comment
 * @property string $contract
 * @property int $contract_id
 * @property int $used_payment_id
 * @property int $event_member_id
 * @property string $created_at
 * @property \DateTime|null $createDate
 *
 * @property User $user
 * @property Group $group
 * @property User $admin
 * @property GroupPupil $groupPupil
 * @property Payment $usedPayment
 * @property EventMember $eventMember
 * @property Payment[] $payments
 * @property int $paymentsSum
 */
class Payment extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%payment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'group_id', 'amount'], 'required'],
            [['user_id', 'group_id', 'admin_id', 'group_pupil_id', 'amount', 'discount', 'used_payment_id', 'event_member_id', 'contract_id'], 'integer'],
            [['comment'], 'string'],
            [['contract'], 'string', 'max' => 50],
            ['discount', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['discount', 'default', 'value' => self::STATUS_INACTIVE],
            [['user_id'], 'exist', 'targetRelation' => 'user', 'filter' => ['role' => User::ROLE_PUPIL]],
            [['admin_id'], 'exist', 'targetRelation' => 'admin'],
            [['group_id'], 'exist', 'targetRelation' => 'group'],
            [['group_pupil_id'], 'exist', 'targetRelation' => 'groupPupil'],
            [['used_payment_id'], 'exist', 'targetRelation' => 'usedPayment'],
            [['event_member_id'], 'exist', 'targetRelation' => 'eventMember'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID записи',
            'user_id' => 'Студент',
            'admin_id' => 'Админ',
            'group_id' => 'Группа',
            'group_pupil_id' => 'Ученик в группе',
            'amount' => 'Сумма',
            'comment' => 'Комментарий',
            'used_payment_id' => 'Использованный при списании платёж',
            'created_at' => 'Дата операции',
        ];
    }

    public function beforeValidate()
    {
        if (empty($this->created_at)) $this->created_at = date('Y-m-d H:i:s');
        if ($this->group_pupil_id && !$this->user_id) {
            $groupPupil = GroupPupil::findOne($this->group_pupil_id);
            $this->user_id = $groupPupil->user_id;
        }
        if (parent::beforeValidate()) {
            if ($this->used_payment_id) {
                $usedPayment = $this->findOne($this->used_payment_id);
                if (!$usedPayment) return false;
                if ($usedPayment->group_pupil_id != $this->group_pupil_id) return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
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
    public function getAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupPupil()
    {
        return $this->hasOne(GroupPupil::class, ['id' => 'group_pupil_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsedPayment()
    {
        return $this->hasOne(Payment::class, ['id' => 'used_payment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventMember()
    {
        return $this->hasOne(EventMember::class, ['id' => 'event_member_id'])->inverseOf('payment');
    }

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(Payment::class, ['used_payment_id' => 'id'])->inverseOf('usedPayment');
    }

    /**
     * @return int
     */
    public function getPaymentsSum()
    {
        return Payment::find()->andWhere(['used_payment_id' => $this->id])->select('SUM(amount)')->scalar();
    }

    /**
     * @return \DateTime|null
     */
    public function getCreateDate(): ?\DateTime
    {
        return empty($this->created_at) ? null : new \DateTime($this->created_at);
    }

    /**
     * @return string
     */
    public function getCreateDateString(): string
    {
        $createDate = $this->getCreateDate();
        return $createDate ? $createDate->format('Y-m-d') : '';
    }
}
