<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\components\helpers\StringGenerator;
use common\models\traits\Inserted;
use common\models\traits\Phone;
use DateTime;
use Exception;

/**
 * This is the model class for table "{{%gift_card}}".
 *
 * @property int $id ID
 * @property string $name Название
 * @property int $amount Номинал
 * @property string $code Проверочный код
 * @property int $status Статус
 * @property string $customer_name ФИО покупателя
 * @property string $customer_phone Телефон покупателя
 * @property string $customer_email E-mail покупателя
 * @property string $additional Дополнительные данные
 * @property array $additionalData Дополнительные данные
 * @property string $paid_at Дата оплаты
 * @property DateTime $paidDate Дата оплаты
 * @property string $paidDateString Дата оплаты
 * @property string $used_at Дата активации
 * @property DateTime $usedDate Дата активации
 * @property string $usedDateString Дата активации
 */
class GiftCard extends ActiveRecord
{
    use Phone, Inserted;

    protected $phoneAttribute = 'customer_phone';

    const STATUS_NEW = 0;
    const STATUS_PAID = 1;
    const STATUS_USED = 2;

    public static $statusList = [
        self::STATUS_NEW,
        self::STATUS_PAID,
        self::STATUS_USED,
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%gift_card}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'customer_name', 'customer_email', 'additional'], 'trim'],
            [['name', 'amount','code', 'customer_name', 'customer_phone', 'customer_email'], 'required'],
            [['amount', 'status'], 'integer'],
            [['name'], 'string', 'max' => 127],
            [['code'], 'string', 'max' => 50],
            [['code'], 'unique'],
            [['additional'], 'string'],
            [['customer_name', 'customer_email'], 'string', 'max' => 255],
            [['customer_phone'], 'string', 'max' => 13],
            ['status', 'in', 'range' => self::$statusList],
            [['additional'], 'default', 'value' => null],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'amount' => 'Номинал',
            'status' => 'Статус',
            'customer_name' => 'ФИО покупателя',
            'customer_phone' => 'Телефон покупателя',
            'customer_email' => 'E-mail покупателя',
            'created_at' => 'Дата заказа',
            'paid_at' => 'Дата оплаты',
            'used_at' => 'Дата активации',
        ];
    }

    /**
     * @return string[]
     */
    public function getAdditionalData(): array
    {
        return json_decode($this->getAttribute('additional'), true) ?: [];
    }

    /**
     * @param string[] $value
     */
    public function setAdditionalData(array $value)
    {
        $this->setAttribute('additional', json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return string
     */
    public function getCreateDateString(): string
    {
        $createDate = $this->createDate;
        return $createDate ? $createDate->format('Y-m-d') : '';
    }

    /**
     * @return DateTime|null
     * @throws Exception
     */
    public function getPaidDate(): ?DateTime
    {
        return empty($this->paid_at) ? null : new DateTime($this->paid_at);
    }

    /**
     * @return string
     */
    public function getPaidDateString(): string
    {
        $paidDate = $this->paidDate;
        return $paidDate ? $paidDate->format('Y-m-d') : '';
    }

    /**
     * @return DateTime|null
     * @throws Exception
     */
    public function getUsedDate(): ?DateTime
    {
        return empty($this->used_at) ? null : new DateTime($this->used_at);
    }

    /**
     * @return string
     */
    public function getUsedDateString(): string
    {
        $usedDate = $this->usedDate;
        return $usedDate ? $usedDate->format('Y-m-d') : '';
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isUsed(): bool
    {
        return $this->status === self::STATUS_USED;
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if ($this->isNewRecord) {
                do {
                    $this->code = StringGenerator::generate(8, true, true);
//                    $this->code = substr(hash('md5', $this->name . mt_rand(1000, mt_getrandmax())), 0, 8);
                } while (self::find()->andWhere(['code' => $this->code])->count('id') > 0);
            }
            return true;
        }
        return false;
    }
}
