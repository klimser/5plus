<?php

namespace common\models;

use common\models\traits\Inserted;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%errors}}".
 *
 * @property string $id
 * @property string $module
 * @property string $content
 */
class Error extends ActiveRecord
{
    use Inserted;


    public static function tableName()
    {
        return '{{%errors}}';
    }


    public function rules()
    {
        return [
            [['module', 'content'], 'required'],
            [['content'], 'string'],
            [['module'], 'string', 'max' => 255],
            [['module', 'content'], 'safe'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'module' => 'Место ошибки',
            'content' => 'Содержимое ошибки',
        ];
    }

    /**
     * @param string $module
     * @param string $content
     */
    public static function logError($module, $content) {
        $error = new Error();
        $error->module = $module;
        $error->content = $content;
        $error->save();
    }
}
