<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "bot_teacher_subject".
 *
 * @property int    $id
 * @property int    $id_teacher
 * @property int    $id_subject
 * @property string $created_at
 * @property string $updated_at
 */
class BotTeacherSubject extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%bot_teacher_subject}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_teacher', 'id_subject'], 'required'],
            [['id_teacher', 'id_subject'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_teacher' => 'Teacher ID',
            'id_subject' => 'Subject ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
