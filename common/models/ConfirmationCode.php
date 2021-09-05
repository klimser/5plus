<?php

namespace common\models;



use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "confirmation_code".
 *
 * @property int $id
 * @property string $phone
 * @property string $code
 * @property string $valid_until
 * @property \DateTimeImmutable $validUntilDate
 */
class ConfirmationCode extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'confirmation_code';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone', 'code'], 'required'],
            [['valid_until'], 'safe'],
            [['phone'], 'string', 'max' => 13],
            [['code'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phone' => 'Phone',
            'code' => 'Code',
            'valid_until' => 'Valid Until',
        ];
    }

    public function setValidUntilDate(\DateTimeInterface $date)
    {
        $this->valid_until = $date->format('Y-m-d H:i:s');
    }

    public function getValidUntilDate(): ?\DateTimeImmutable
    {
        return $this->valid_until ? new \DateTimeImmutable($this->valid_until) : null;
    }
}
