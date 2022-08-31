<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%payment_link}}".
 *
 * @property int    $id ID
 * @property string $hash_key ключ
 * @property int    $user_id Студент
 * @property int    $group_id Группа
 * @property string $url
 *
 * @property User   $user
 * @property Course $group
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
            [['hash_key', 'user_id', 'group_id'], 'required'],
            [['user_id', 'group_id'], 'integer'],
            [['hash_key'], 'string', 'max' => 25],
            [['hash_key'], 'unique'],
            [['user_id', 'group_id'], 'unique', 'targetAttribute' => ['user_id', 'group_id']],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
            [['group_id'], 'exist', 'targetRelation' => 'group'],
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
            'group_id' => 'Группа',
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
    public function getGroup()
    {
        return $this->hasOne(Course::class, ['id' => 'group_id']);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return Url::to(['/payment/link', 'key' => $this->hash_key], true);
    }
}
