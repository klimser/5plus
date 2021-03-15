<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\Group;
use common\models\Teacher;
use common\models\traits\Inserted;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "group_note".
 *
 * @property int $id
 * @property int $group_id
 * @property int $teacher_id
 * @property string $topic
 * @property string $created_at
 *
 * @property Group $group
 * @property Teacher $teacher
 */
class GroupNote extends ActiveRecord
{
    use Inserted;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group_note';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_id', 'teacher_id', 'topic'], 'required'],
            [['group_id', 'teacher_id'], 'integer'],
            [['topic'], 'string', 'max' => 255],
            [['group_id'], 'exist', 'targetRelation' => 'group'],
            [['teacher_id'], 'exist', 'targetRelation' => 'teacher'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => 'Group ID',
            'teacher_id' => 'Teacher ID',
            'topic' => 'Topic',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Group]].
     *
     * @return ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * Gets query for [[Teacher]].
     *
     * @return ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(Teacher::class, ['id' => 'teacher_id']);
    }
}
