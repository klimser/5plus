<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "bot_teacher".
 *
 * @property int    $id
 * @property array  $name
 * @property string $url
 * @property string $photo
 * @property array  $teaser
 * @property array  $description
 * @property string $created_at
 * @property string $updated_at
 */
class BotTeacher extends ActiveRecord
{
    public const int CHIEF_OF_THE_BOARD_ID = 4;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%bot_teacher}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'url'], 'required'],
            [['url'], 'string', 'max' => 100],
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
            'url' => 'URL',
            'photo' => 'Photo',
            'teaser' => 'Teaser',
            'description' => 'Description',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
