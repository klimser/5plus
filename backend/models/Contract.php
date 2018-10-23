<?php

namespace backend\models;

use backend\components\GroupComponent;
use \common\components\extended\ActiveRecord;
use common\components\helpers\Money;
use common\models\traits\Inserted;

/**
 * This is the model class for table "{{%contract}}".
 *
 * @property int $id ID
 * @property string $number Номер договора
 * @property int $user_id ID ученика
 * @property int $group_id ID группы
 * @property int $amount Сумма
 * @property int $discount Скидочный платёж
 * @property string $amountString Сумма прописью
 * @property int $status Статус оплаты
 * @property int $payment_type Тип оплаты
 * @property string $external_id ID транзакции в платёжной системе
 * @property string $created_at Дата создания
 * @property string $paid_at Дата оплаты
 * @property int $created_admin_id Кто добавил
 * @property int $paid_admin_id Кто отметил оплаченным
 * @property \DateTime|null $createDate
 * @property \DateTime|null $paidDate
 * @property string $paidDateString
 * @property int $lessonsCount
 * @property int $weeksCount
 *
 * @property User $user
 * @property Group $group
 * @property GroupParam $groupParam
 * @property Payment[] $payments
 * @property User $createdAdmin
 * @property User $paidAdmin
 */
class Contract extends ActiveRecord
{
    const STATUS_NEW = 0;
    const STATUS_PROCESS = 1;
    const STATUS_PAID = 2;

    const PAYMENT_TYPE_MANUAL = 1;
    const PAYMENT_TYPE_PAYME = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%contract}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['number', 'user_id', 'group_id', 'amount'], 'required'],
            [['user_id', 'amount', 'discount', 'status', 'payment_type'], 'integer'],
            [['created_at', 'paid_at'], 'safe'],
            [['number'], 'string', 'max' => 20],
            [['external_id'], 'string', 'max' => 50],
            [['number'], 'unique'],
            ['discount', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['discount', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_NEW, self::STATUS_PROCESS, self::STATUS_PAID]],
            ['status', 'default', 'value' => self::STATUS_NEW],
            ['payment_type', 'in', 'range' => [self::PAYMENT_TYPE_MANUAL, self::PAYMENT_TYPE_PAYME]],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
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
            'number' => 'Номер договора',
            'user_id' => 'Студент',
            'group_id' => 'Группа',
            'amount' => 'Сумма',
            'discount' => 'Скидочный платёж',
            'status' => 'Статус оплаты',
            'payment_type' => 'Тип оплаты',
            'external_id' => 'ID транзакции в платёжной системе',
            'created_at' => 'Дата договора',
            'paid_at' => 'Дата оплаты',
            'created_admin_id' => 'Кто добавил',
            'paid_admin_id' => 'Кто отметил оплаченным',
        ];
    }

    public function beforeValidate() {
        if (empty($this->created_at)) $this->created_at = date('Y-m-d H:i:s');
        if (!parent::beforeValidate()) return false;

        if ($this->getOldAttribute('status') == self::STATUS_PAID
            && ($this->status != self::STATUS_PAID || $this->amount != $this->getOldAttribute('amount'))) {
            $this->addError('status', 'Paid contract cannot be changed!');
            return false;
        }
        return true;
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

    /**
     * @return \DateTime|null
     */
    public function getPaidDate(): ?\DateTime
    {
        return empty($this->paid_at) ? null : new \DateTime($this->paid_at);
    }

    /**
     * @return string
     */
    public function getPaidDateString(): string
    {
        $paidDate = $this->getPaidDate();
        return $paidDate ? $paidDate->format('Y-m-d H:i:s') : '';
    }

    /**
     * @return string
     */
    public function getAmountString(): string
    {
        return Money::numberToStringRus($this->amount, true);
    }

    public function getGroupParam(): GroupParam
    {
        $groupParam = GroupParam::findByDate($this->group, $this->createDate);
        if ($groupParam === null) {
            $groupParam = new GroupParam();
            $groupParam->weekday = $this->group->weekday;
            $groupParam->lesson_price = $this->group->lesson_price;
            $groupParam->lesson_price_discount = $this->group->lesson_price_discount;
        };
        return $groupParam;
    }

    /**
     * @return int
     */
    public function getLessonsCount(): int
    {
        return round($this->discount ? $this->amount / $this->groupParam->lesson_price_discount : $this->amount / $this->groupParam->lesson_price);
    }

    /**
     * @return int
     */
    public function getWeeksCount(): int
    {
        return round($this->lessonsCount / GroupComponent::getWeekClasses($this->groupParam->weekday));
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
    public function getPayments()
    {
        return $this->hasMany(Payment::class, ['contract_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'created_admin_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaidAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'paid_admin_id']);
    }
}
