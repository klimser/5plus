<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%module_order_subject}}".
 *
 * @property string $name
 */
class OrderSubject extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%module_order_subject}}';
    }


    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 127],
        ];
    }


    public function attributeLabels()
    {
        return [
            'name' => 'Предмет',
        ];
    }
}
