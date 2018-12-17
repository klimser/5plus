<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%widget_html}}".
 *
 * @property string $id
 * @property string $name
 * @property string $content
 * @property int $editor
 */
class WidgetHtml extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%widget_html}}';
    }


    public function rules()
    {
        return [
            [['name'], 'required'],
            [['content'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['editor'], 'integer'],
            [['editor'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['editor'], 'default', 'value' => self::STATUS_INACTIVE],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'content' => 'Содержимое',
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        \common\components\WidgetHtml::clearCache($this);
    }

    public function beforeDelete()
    {
        \common\components\WidgetHtml::clearCache($this);
        return parent::beforeDelete();
    }
}
