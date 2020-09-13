<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\components\helpers\MoneyHelper;
use common\models\traits\Inserted;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%contract}}".
 *
 * @property int $id ID
 * @property string $number Номер договора
 * @property int $user_id ID ученика
 * @property int $group_id ID группы
 * @property int $company_id ID компании
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
 * @property Company $company
 * @property GroupPupil $activeGroupPupil
 */
class Contract extends ActiveRecord
{
    use Inserted;
    
    const STATUS_NEW = 0;
    const STATUS_PROCESS = 1;
    const STATUS_PAID = 2;

    const PAYMENT_TYPE_MANUAL = 1;
    const PAYMENT_TYPE_PAYME = 2;
    const PAYMENT_TYPE_PAYMO = 3;
    const PAYMENT_TYPE_CLICK = 4;
    
    const STATUS_LABELS = [
        self::STATUS_NEW => 'не оплачен',
        self::STATUS_PROCESS => 'не завершен',
        self::STATUS_PAID => 'оплачен',
    ];
    
    const PAYMENT_TYPE_LABELS = [
        self::PAYMENT_TYPE_MANUAL => 'офис',
        self::PAYMENT_TYPE_PAYME => 'Payme',
        self::PAYMENT_TYPE_PAYMO => 'PAYMO',
    ];

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
            [['number', 'user_id', 'group_id', 'company_id', 'amount'], 'required'],
            [['user_id', 'amount', 'discount', 'status', 'payment_type'], 'integer'],
            [['created_at', 'paid_at'], 'safe'],
            [['number'], 'string', 'max' => 20],
            [['external_id'], 'string', 'max' => 50],
            [['number'], 'unique'],
            ['discount', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['discount', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_NEW, self::STATUS_PROCESS, self::STATUS_PAID]],
            ['status', 'default', 'value' => self::STATUS_NEW],
            ['payment_type', 'in', 'range' => [self::PAYMENT_TYPE_MANUAL, self::PAYMENT_TYPE_PAYME, self::PAYMENT_TYPE_PAYMO]],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
            [['group_id'], 'exist', 'targetRelation' => 'group'],
            [['company_id'], 'exist', 'targetRelation' => 'company'],
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
        if (!parent::beforeValidate()) return false;

        if ($this->isNewRecord && empty($this->number)) {
            if (!$this->user_id) {
                $this->addError('user_id', 'Assign user to contract first!');
                return false;
            }

            $numberPrefix = date('Ymd') . $this->user_id;
            $numberAffix = 1;
            while (Contract::find()->andWhere(['number' => $numberPrefix . $numberAffix])->select('COUNT(id)')->scalar() > 0) {
                $numberAffix++;
            }
            $this->number = $numberPrefix . $numberAffix;
        }

        if ($this->getOldAttribute('status') == self::STATUS_PAID
            && ($this->status != self::STATUS_PAID || $this->amount != $this->getOldAttribute('amount'))) {
            $this->addError('status', 'Paid contract cannot be changed!');
            return false;
        }
        return true;
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
        return $paidDate ? $paidDate->format('Y-m-d') : '';
    }

    /**
     * @return string
     */
    public function getAmountString(): string
    {
        return MoneyHelper::numberToStringRus($this->amount, true);
    }

    public function getGroupParam(): GroupParam
    {
        $groupParam = GroupParam::findByDate($this->group, $this->createDate);
        if ($groupParam === null) {
            $groupParam = new GroupParam();
            $groupParam->schedule = $this->group->schedule;
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
        return round($this->lessonsCount / $this->groupParam->classesPerWeek);
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(Payment::class, ['contract_id' => 'id'])->inverseOf('contract');
    }

    /**
     * @return ActiveQuery
     */
    public function getCreatedAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'created_admin_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getPaidAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'paid_admin_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::class, ['id' => 'company_id']);
    }
    
    public function isNew()
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * @return ActiveQuery
     */
    public function getActiveGroupPupil()
    {
        return $this->hasOne(GroupPupil::class, ['group_id' => 'group_id', 'user_id' => 'user_id'])
            ->andWhere('active = :active', [':active' => GroupPupil::STATUS_ACTIVE])
            ->orderBy(['date_start' => SORT_DESC]);
    }
}
