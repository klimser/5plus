<?php

namespace common\models;

use common\components\CourseComponent;
use common\components\extended\ActiveRecord;
use common\components\helpers\MoneyHelper;
use common\models\traits\Inserted;
use DateTimeImmutable;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%contract}}".
 *
 * @property int    $id ID
 * @property string $number Номер договора
 * @property int    $user_id ID ученика
 * @property int    $course_id ID группы
 * @property int    $company_id ID компании
 * @property int    $amount Сумма
 * @property int    $discount Скидочный платёж
 * @property string $amountString Сумма прописью
 * @property int    $status Статус оплаты
 * @property int    $payment_type Тип оплаты
 * @property string $external_id ID транзакции в платёжной системе
 * @property string $created_at Дата создания
 * @property string $paid_at Дата оплаты
 * @property int            $created_admin_id Кто добавил
 * @property int            $paid_admin_id Кто отметил оплаченным
 * @property \DateTime|null $createDate
 * @property \DateTime|null $paidDate
 * @property string         $paidDateString
 * @property int            $lessonsCount
 * @property int            $weeksCount
 * @property int            $monthCount
 *
 * @property User           $user
 * @property Course         $course
 * @property CourseConfig   $courseConfig
 * @property Payment[]      $payments
 * @property User           $createdAdmin
 * @property User           $paidAdmin
 * @property Company        $company
 * @property CourseStudent  $activeCourseStudent
 */
class Contract extends ActiveRecord
{
    use Inserted;

    public const STATUS_NEW     = 0;
    public const STATUS_PROCESS = 1;
    public const STATUS_PAID    = 2;

    public const PAYMENT_TYPE_MANUAL         = 1;
    public const PAYMENT_TYPE_PAYME          = 2;
    public const PAYMENT_TYPE_ATMOS          = 3;
    public const PAYMENT_TYPE_CLICK          = 4;
    public const PAYMENT_TYPE_TELEGRAM_PAYME = 5;
    public const PAYMENT_TYPE_MANUAL_CASH = 6;
    public const PAYMENT_TYPE_MANUAL_UZKARD = 7;
    public const PAYMENT_TYPE_MANUAL_HUMO = 8;
    public const PAYMENT_TYPE_MANUAL_PAYME = 9;
    public const PAYMENT_TYPE_MANUAL_BANK = 10;
    public const PAYMENT_TYPE_MANUAL_OLD = 11;
    public const PAYMENT_TYPE_APELSIN = 12;
    public const PAYMENT_TYPE_PAYBOX = 13;

    public const STATUS_LABELS = [
        self::STATUS_NEW => 'не оплачен',
        self::STATUS_PROCESS => 'не завершен',
        self::STATUS_PAID => 'оплачен',
    ];

    public const PAYMENT_TYPE_LABELS = [
        self::PAYMENT_TYPE_MANUAL => 'офис',
        self::PAYMENT_TYPE_PAYME => 'Payme',
        self::PAYMENT_TYPE_ATMOS => 'ATMOS',
        self::PAYMENT_TYPE_CLICK => 'CLICK',
        self::PAYMENT_TYPE_TELEGRAM_PAYME => 'Telegram (Payme)',
        self::PAYMENT_TYPE_MANUAL_CASH => 'Наличные',
        self::PAYMENT_TYPE_MANUAL_UZKARD => 'терминал Uzkard',
        self::PAYMENT_TYPE_MANUAL_HUMO => 'терминал HUMO',
        self::PAYMENT_TYPE_MANUAL_PAYME => 'Payme приложение',
        self::PAYMENT_TYPE_MANUAL_BANK => 'Банковский перевод (юр лица)',
        self::PAYMENT_TYPE_MANUAL_OLD => 'прошлый учебный год',
        self::PAYMENT_TYPE_APELSIN => 'Apelsin',
        self::PAYMENT_TYPE_PAYBOX => 'PayBox',
    ];

    public const MANUAL_PAYMENT_TYPES = [
        self::PAYMENT_TYPE_MANUAL_CASH,
        self::PAYMENT_TYPE_MANUAL_UZKARD,
        self::PAYMENT_TYPE_MANUAL_HUMO,
        self::PAYMENT_TYPE_MANUAL_PAYME,
        self::PAYMENT_TYPE_MANUAL_BANK,
        self::PAYMENT_TYPE_MANUAL_OLD,
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
            [['number', 'user_id', 'course_id', 'company_id', 'amount'], 'required'],
            [['user_id', 'amount', 'discount', 'status', 'payment_type'], 'integer'],
            [['created_at', 'paid_at'], 'safe'],
            [['number'], 'string', 'max' => 20],
            [['external_id'], 'string', 'max' => 50],
            [['number'], 'unique'],
            ['discount', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['discount', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_NEW, self::STATUS_PROCESS, self::STATUS_PAID]],
            ['status', 'default', 'value' => self::STATUS_NEW],
            ['payment_type', 'in', 'range' => [
                self::PAYMENT_TYPE_MANUAL,
                self::PAYMENT_TYPE_PAYME,
                self::PAYMENT_TYPE_ATMOS,
                self::PAYMENT_TYPE_CLICK,
                self::PAYMENT_TYPE_TELEGRAM_PAYME,
                self::PAYMENT_TYPE_MANUAL_CASH,
                self::PAYMENT_TYPE_MANUAL_UZKARD,
                self::PAYMENT_TYPE_MANUAL_HUMO,
                self::PAYMENT_TYPE_MANUAL_PAYME,
                self::PAYMENT_TYPE_MANUAL_BANK,
                self::PAYMENT_TYPE_MANUAL_OLD,
                self::PAYMENT_TYPE_APELSIN,
                self::PAYMENT_TYPE_PAYBOX,
            ]],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
            [['course_id'], 'exist', 'targetRelation' => 'course'],
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
            'course_id' => 'Группа',
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

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

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

    public function getCreateDateString(): string
    {
        $createDate = $this->getCreateDate();

        return $createDate ? $createDate->format('Y-m-d') : '';
    }

    public function getPaidDate(): ?DateTimeImmutable
    {
        return empty($this->paid_at) ? null : new DateTimeImmutable($this->paid_at);
    }

    public function getPaidDateString(): string
    {
        $paidDate = $this->getPaidDate();

        return $paidDate ? $paidDate->format('Y-m-d') : '';
    }

    public function getAmountString(): string
    {
        return MoneyHelper::numberToStringRus($this->amount, true);
    }

    public function getCourseConfig(): CourseConfig
    {
        if ($courseConfig = CourseComponent::getCourseConfig($this->course, $this->createDate, false)) {
            return $courseConfig;
        }

        if ($this->course->startDateObject > $this->createDate) {
            return $this->course->courseConfigs[0];
        } else {
            return $this->course->latestCourseConfig;
        }
    }

    public function getLessonsCount(): int
    {
        return (int) round($this->amount / ($this->discount ? $this->courseConfig->lesson_price_discount : $this->courseConfig->lesson_price));
    }

    public function getWeeksCount(): int
    {
        return (int) round($this->lessonsCount / $this->courseConfig->classesPerWeek);
    }

    public function getMonthCount(): float
    {
        return round($this->lessonsCount / $this->courseConfig->classesPerMonth, 2);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }

    public function getPayments(): ActiveQuery
    {
        return $this->hasMany(Payment::class, ['contract_id' => 'id'])->inverseOf('contract');
    }

    public function getCreatedAdmin(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_admin_id']);
    }

    public function getPaidAdmin(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'paid_admin_id']);
    }

    public function getCompany(): ActiveQuery
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

    public function getActiveCourseStudent(): ActiveQuery
    {
        return $this->hasOne(CourseStudent::class, ['course_id' => 'course_id', 'user_id' => 'user_id'])
            ->andWhere('active = :active', [':active' => CourseStudent::STATUS_ACTIVE])
            ->orderBy(['date_start' => SORT_DESC]);
    }
}
