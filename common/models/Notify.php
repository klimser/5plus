<?php

namespace common\models;

use common\models\traits\Inserted;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%notify}}".
 *
 * @property int            $id ID
 * @property int            $user_id ID студента
 * @property int            $course_id Группа
 * @property int            $template_id ID шаблона
 * @property array|null     $parameters Параметры для сообщения
 * @property int            $status Статус отправки сообщения
 * @property string         $created_at Дата создания
 * @property string         $sent_at Дата успешной отправки
 * @property int            $attempts Попыток отправки
 * @property \DateTime|null $sentDate
 *
 * @property User           $user
 * @property Course         $course
 */
class Notify extends ActiveRecord
{
    use Inserted;

    const STATUS_NEW = 0;
    const STATUS_SENDING = 1;
    const STATUS_SENT = 2;
    const STATUS_ERROR = 3;

    const TEMPLATE_STUDENT_DEBT = 1;
    const TEMPLATE_PARENT_DEBT = 2;
    const TEMPLATE_STUDENT_LOW = 3;
    const TEMPLATE_PARENT_LOW  = 4;
    const TEMPLATE_WELCOME_LESSON = 5;

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
            [['user_id', 'template_id', 'params'], 'required'],
            [['user_id', 'course_id' , 'template_id', 'status', 'attempts'], 'integer'],
            [['params'], 'string'],
            ['status', 'in', 'range' => [self::STATUS_NEW, self::STATUS_SENDING, self::STATUS_SENT, self::STATUS_ERROR]],
            ['status', 'default', 'value' => self::STATUS_NEW],
            [['created_at', 'sent_at'], 'safe'],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
            [['course_id'], 'exist', 'targetRelation' => 'course'],
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
            'course_id' => 'Группа',
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }

    /**
     * @return array|null
     */
    public function getParameters(): ?array
    {
        return $this->getAttribute('params') ? json_decode($this->getAttribute('params') ?? '', true) : null;
    }

    /**
     * @param array|null|string $params
     */
    public function setParameters(?array $params)
    {
        $this->setAttribute('params', $params ? json_encode($params) : null);
    }

    /**
     * @return \DateTime|null
     */
    public function getSentDate(): ?\DateTime
    {
        return empty($this->sent_at) ? null : new \DateTime($this->sent_at);
    }
}
