<?php

namespace common\models;

use backend\models\EventMember;
use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%payment}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int $group_id
 * @property int $admin_id
 * @property int $amount
 * @property int $discount Скидочный платёж
 * @property string $comment
 * @property int $contract_id
 * @property int $used_payment_id
 * @property int $event_member_id
 * @property int $cash_received
 * @property int $bitrix_sync_status
 * @property string $created_at
 * @property \DateTime|null $createDate
 *
 * @property Contract $contract
 * @property User $user
 * @property Group $group
 * @property User $admin
 * @property Payment $usedPayment
 * @property EventMember $eventMember
 * @property Payment[] $payments
 * @property int $paymentsSum
 * @property int $moneyLeft
 */
class Payment extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%payment}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'group_id', 'amount'], 'required'],
            [['user_id', 'group_id', 'admin_id', 'amount', 'discount', 'used_payment_id', 'event_member_id', 'contract_id', 'cash_received', 'bitrix_sync_status'], 'integer'],
            [['comment'], 'string'],
            [['discount', 'bitrix_sync_status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['discount',  'bitrix_sync_status'], 'default', 'value' => self::STATUS_INACTIVE],
            ['cash_received', 'default', 'value' => self::STATUS_ACTIVE],
            ['user_id', 'exist', 'targetRelation' => 'user', 'filter' => ['role' => User::ROLE_PUPIL]],
            ['admin_id', 'exist', 'targetRelation' => 'admin'],
            ['group_id', 'exist', 'targetRelation' => 'group'],
            ['contract_id', 'exist', 'targetRelation' => 'contract'],
            ['used_payment_id', 'exist', 'targetRelation' => 'usedPayment'],
            ['event_member_id', 'exist', 'targetRelation' => 'eventMember'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID записи',
            'user_id' => 'Студент',
            'admin_id' => 'Админ',
            'group_id' => 'Группа',
            'amount' => 'Сумма',
            'comment' => 'Комментарий',
            'used_payment_id' => 'Использованный при списании платёж',
            'cash_received' => 'Получены ли физически деньги',
            'created_at' => 'Дата операции',
        ];
    }

    public function beforeValidate()
    {
        if (empty($this->created_at)) $this->created_at = date('Y-m-d H:i:s');
        if (parent::beforeValidate()) {
            if ($this->used_payment_id) {
                $usedPayment = $this->findOne($this->used_payment_id);
                if (!$usedPayment) return false;
                if ($usedPayment->group_id != $this->group_id) return false;
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
    public function getContract()
    {
        return $this->hasOne(Contract::class, ['id' => 'contract_id']);
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
        return Payment::find()->andWhere(['used_payment_id' => $this->id])->select('SUM(amount)')->scalar() * (-1);
    }

    /**
     * @return int
     */
    public function getMoneyLeft()
    {
        return $this->amount - $this->paymentsSum;
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
        $createDate = $this->createDate;
        return $createDate ? $createDate->format('Y-m-d') : '';
    }
}
