<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\Inserted;
use DateTimeImmutable;
use DateTimeInterface;
use Yii;

/**
 * This is the model class for table "age_confirmation".
 *
 * @property int $id
 * @property int $user_id
 * @property string $phone
 * @property string $hash
 * @property string $status
 * @property int $attempt
 * @property string|null $sent_at
 * @property string|null $confirmed_at
 * @property string|null $valid_until
 * @property string $created_at
 * @property DateTimeImmutable $validUntilDate
 * @property  DateTimeImmutable $sentDate
 *
 * @property User $user
 */
class AgeConfirmation extends ActiveRecord
{
    public const STATUS_NEW = 'new';
    public const STATUS_SENT = 'sent';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_CONFIRMED = 'confirmed';

    use Inserted;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'age_confirmation';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'phone', 'hash'], 'required'],
            [['user_id', 'attempt'], 'integer'],
            [['sent_at', 'confirmed_at', 'valid_until', 'created_at'], 'safe'],
            [['phone'], 'string', 'max' => 13],
            [['hash'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 20],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'phone' => 'Phone',
            'hash' => 'Hash',
            'status' => 'Status',
            'attempt' => 'Attempts',
            'sent_at' => 'Sent At',
            'confirmed_at' => 'Confirmed At',
            'valid_until' => 'Valid Until',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function setValidUntilDate(DateTimeInterface $date)
    {
        $this->valid_until = $date->format('Y-m-d H:i:s');
    }

    public function getValidUntilDate(): ?DateTimeImmutable
    {
        return $this->valid_until ? new DateTimeImmutable($this->valid_until) : null;
    }

    public function setSentDate(DateTimeInterface $date)
    {
        $this->sent_at = $date->format('Y-m-d H:i:s');
    }

    public function getSentDate(): ?DateTimeImmutable
    {
        return $this->sent_at ? new DateTimeImmutable($this->sent_at) : null;
    }
    
    public function generateHash(string $code): string
    {
        $this->hash = Yii::$app->security->generatePasswordHash($code);
        return $this->hash;
    }
    
    public function validateHash(string $code): bool
    {
        if (!$this->hash) {
            return false;
        }

        return Yii::$app->security->validatePassword($code, $this->hash);
    }
}
