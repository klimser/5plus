<?php

namespace common\models;

use backend\models\EventMember;
use common\components\extended\ActiveRecord;
use DateTime;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%payment}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property int $admin_id
 * @property int $amount
 * @property int $discount Скидочный платёж
 * @property string        $comment
 * @property int           $contract_id
 * @property int           $used_payment_id
 * @property int           $event_member_id
 * @property int           $cash_received
 * @property string        $created_at
 * @property DateTime|null $createDate
 *
 * @property Contract      $contract
 * @property User          $user
 * @property Course        $course
 * @property CourseConfig  $courseConfig
 * @property User          $admin
 * @property Payment       $usedPayment
 * @property EventMember   $eventMember
 * @property Payment[]     $payments
 * @property int           $paymentsSum
 * @property int           $moneyLeft
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
            [['user_id', 'course_id', 'amount'], 'required'],
            [['user_id', 'course_id', 'admin_id', 'amount', 'discount', 'used_payment_id', 'event_member_id', 'contract_id', 'cash_received'], 'integer'],
            [['comment'], 'string'],
            [['discount'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['discount'], 'default', 'value' => self::STATUS_INACTIVE],
            ['cash_received', 'default', 'value' => self::STATUS_ACTIVE],
            ['user_id', 'exist', 'targetRelation' => 'user', 'filter' => ['role' => User::ROLE_STUDENT]],
            ['admin_id', 'exist', 'targetRelation' => 'admin'],
            ['course_id', 'exist', 'targetRelation' => 'course'],
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
            'course_id' => 'Группа',
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
                if ($usedPayment->course_id != $this->course_id) return false;
            }
            return true;
        }
        return false;
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getContract(): ActiveQuery
    {
        return $this->hasOne(Contract::class, ['id' => 'contract_id'])->inverseOf('payments');
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }

    public function getAdmin(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }

    public function getUsedPayment(): ActiveQuery
    {
        return $this->hasOne(Payment::class, ['id' => 'used_payment_id'])->inverseOf('payments');
    }

    public function getEventMember(): ActiveQuery
    {
        return $this->hasOne(EventMember::class, ['id' => 'event_member_id'])->inverseOf('payments');
    }

    public function getPayments(): ActiveQuery
    {
        return $this->hasMany(Payment::class, ['used_payment_id' => 'id'])->inverseOf('usedPayment');
    }

    public function getCourseConfig(): ActiveQuery
    {
        return $this->hasOne(CourseConfig::class, ['course_id' => 'course_id'])
            ->andWhere(['and', 'date_from <= :payment_date', ['or', 'date_to IS NULL', 'date_to > :payment_date']], ['payment_date' => $this->created_at]);
    }

    public function getPaymentsSum(): int
    {
        return (int)(Payment::find()->andWhere(['used_payment_id' => $this->id])->select('SUM(amount)')->scalar() * (-1));
    }

    public function getMoneyLeft(): int
    {
        return $this->amount - $this->paymentsSum;
    }

    /**
     * @return DateTime|null
     * @throws \Exception
     */
    public function getCreateDate(): ?DateTime
    {
        return empty($this->created_at) ? null : new DateTime($this->created_at);
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
