<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "bot_subject".
 *
 * @property int    $id
 * @property array  $name
 * @property string $url
 * @property string $created_at
 * @property string $updated_at
 */
class BotSubject extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%bot_subject}}';
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
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
