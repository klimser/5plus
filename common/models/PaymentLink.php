<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use yii\db\ActiveQuery;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%payment_link}}".
 *
 * @property int    $id ID
 * @property string $hash_key ключ
 * @property int    $user_id Студент
 * @property int    $course_id Группа
 * @property string $url
 *
 * @property User   $user
 * @property Course $course
 */
class PaymentLink extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%payment_link}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['hash_key', 'user_id', 'course_id'], 'required'],
            [['user_id', 'course_id'], 'integer'],
            [['hash_key'], 'string', 'max' => 25],
            [['hash_key'], 'unique'],
            [['user_id', 'course_id'], 'unique', 'targetAttribute' => ['user_id', 'course_id']],
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
            'hash_key' => 'ключ',
            'user_id' => 'Студент',
            'course_id' => 'Группа',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }

    public function getUrl(): string
    {
        return Url::to(['/payment/link', 'key' => $this->hash_key], true);
    }
}
