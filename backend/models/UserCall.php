<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\traits\Inserted;
use common\models\User;

/**
 * This is the model class for table "{{%user_call}}".
 *
 * @property int $id ID
 * @property int $user_id ID студента
 * @property int $admin_id ID админа
 * @property string $comment Комментарий
 *
 * @property User $user
 * @property User $admin
 */
class UserCall extends ActiveRecord
{
    use Inserted;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_call}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'admin_id'], 'required'],
            [['user_id', 'admin_id'], 'integer'],
            [['comment'], 'string'],
            ['user_id', 'exist', 'targetRelation' => 'user'],
            ['admin_id', 'exist', 'targetRelation' => 'admin'],
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
            'admin_id' => 'ID админа',
            'comment' => 'Комментарий',
            'created_at' => 'Дата звонка',
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
    public function getAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }
}
