<?php

namespace backend\models;

use common\models\traits\Inserted;
use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%action}}".
 *
 * @property int $id
 * @property int $type
 * @property int $admin_id
 * @property int $user_id
 * @property int $group_id
 * @property int $amount
 * @property string $comment
 *
 * @property User $admin
 * @property User $user
 * @property Group $group
 */
class Action extends ActiveRecord
{
    use Inserted;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%action}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'admin_id', 'user_id', 'amount'], 'required'],
            [['type', 'admin_id', 'user_id', 'group_id', 'amount'], 'integer'],
            [['comment'], 'string', 'max' => 255],
            [['admin_id'], 'exist', 'targetRelation' => 'admin'],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
            [['group_id'], 'exist', 'targetRelation' => 'group'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID записи',
            'type' => 'Тип записи',
            'admin_id' => 'Админ',
            'user_id' => 'Клиент',
            'group_id' => 'Группа',
            'amount' => 'Сумма',
            'comment' => 'Комментарий',
            'created_at' => 'Дата операции',
        ];
    }

    /**
    * @return \yii\db\ActiveQuery
    */
    public function getAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
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
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }
}
