<?php

namespace backend\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%group_type}}".
 *
 * @property int $id ID
 * @property string $name
 *
 * @property Group[] $groups
 */
class GroupType extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%group_type}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroups()
    {
        return $this->hasMany(Group::class, ['type' => 'id']);
    }
}
