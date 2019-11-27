<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "bot_queue".
 *
 * @property int $id
 * @property string $path
 * @property string $payload
 * @property string $valid_until
 * @property string $lock
 * @property-read \DateTime $lockTime
 */
class BotQueue extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'bot_queue';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['path', 'valid_until'], 'required'],
            [['payload', 'valid_until'], 'safe'],
            [['path'], 'string', 'max' => 50],
            [['payload', 'lock'], 'string']
        ];
    }
    
    public function getLockTime(): ?\DateTime
    {
        return $this->lock ? date_create($this->lock) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'path' => 'Path',
            'payload' => 'Payload',
            'valid_until' => 'Valid Until',
        ];
    }
}
