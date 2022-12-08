<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "bot_push".
 *
 * @property int   $id
 * @property int   $chat_id
 * @property array $message_data
 * @property array $result_data
 * @property int   $status
 */
class BotPush extends ActiveRecord
{
    const STATUS_NEW = 0;
    const STATUS_SENDING = 1;
    const STATUS_SENT = 2;
    const STATUS_ERROR = 3;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%bot_push}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['chat_id'], 'required'],
            [['chat_id', 'status'], 'integer'],
            [['message', 'data'], 'safe'],
            ['status', 'in', 'range' => [self::STATUS_NEW, self::STATUS_SENDING, self::STATUS_SENT, self::STATUS_ERROR]],
            ['status', 'default', 'value' => self::STATUS_NEW],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_id' => 'Chat ID',
            'data' => 'Data',
            'status' => 'Status',
        ];
    }

    /**
     * @return array|null
     */
    public function getMessageArray(): array
    {
        return $this->getAttribute('message') ? json_decode($this->getAttribute('message') ?? '', true) : [];
    }

    /**
     * @param array|null|string $params
     */
    public function setMessageArray(?array $params)
    {
        $this->setAttribute('message', $params ? json_encode($params, JSON_UNESCAPED_UNICODE) : null);
    }

    /**
     * @return array|null
     */
    public function getDataArray(): ?array
    {
        return $this->getAttribute('data') ? json_decode($this->getAttribute('data') ?? '', true) : null;
    }

    /**
     * @param array|null $data
     */
    public function setDataArray(?array $data)
    {
        $this->setAttribute('data', $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null);
    }
}
