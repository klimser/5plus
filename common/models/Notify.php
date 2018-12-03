<?php

namespace common\models;

use common\models\traits\Inserted;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%notify}}".
 *
 * @property int $id ID
 * @property int $user_id ID студента
 * @property int $template_id ID шаблона
 * @property string $params Параметры для сообщения
 * @property int $status Статус отправки сообщения
 * @property string $created_at Дата создания
 * @property string $sent_at Дата успешной отправки
 * @property int $attempts Попыток отправки
 *
 * @property User $user
 */
class Notify extends ActiveRecord
{
    use Inserted;

    const STATUS_NEW = 0;
    const STATUS_SENT = 1;
    const STATUS_ERROR = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%notify}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'template_id', 'params', 'created_at'], 'required'],
            [['user_id', 'template_id', 'status', 'attempts'], 'integer'],
            [['params'], 'string'],
            ['status', 'in', 'range' => [self::STATUS_NEW, self::STATUS_SENT, self::STATUS_ERROR]],
            ['status', 'default', 'value' => self::STATUS_NEW],
            [['created_at', 'sent_at'], 'safe'],
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
            'user_id' => 'ID студента',
            'template_id' => 'ID шаблона',
            'params' => 'Параметры для сообщения',
            'status' => 'Статус отправки сообщения',
            'created_at' => 'Дата создания',
            'sent_at' => 'Дата успешной отправки',
            'attempts' => 'Попыток отправки',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
