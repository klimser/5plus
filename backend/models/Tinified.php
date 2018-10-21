<?php

namespace backend\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%tinified}}".
 *
 * @property string $fileName
 * @property string $checksum
 */
class Tinified extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tinified}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fileName', 'checksum'], 'required'],
            [['fileName'], 'string', 'max' => 127],
            [['checksum'], 'string', 'max' => 40],
            [['fileName'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'fileName' => 'File Name',
            'checksum' => 'Checksum',
        ];
    }
}
