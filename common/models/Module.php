<?php

namespace common\models;

use \common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%module}}".
 *
 * @property int $id
 * @property string $controller
 * @property string $action
 * @property string $description
 * @property string $url_prefix
 * @property string $field_for_url
 * @property string $field_for_title
 * @property int $active
 *
 * @property Webpage[] $webpages
 */
class Module extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%module}}';
    }


    public function rules()
    {
        return [
            [['description'], 'required'],
            [['active'], 'integer'],
            [['controller', 'action', 'url_prefix', 'field_for_url', 'field_for_title'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 255],
            [['controller', 'action'], 'unique', 'targetAttribute' => ['controller', 'action'], 'message' => 'The combination of Controller and Action has already been taken.']
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'controller' => 'Controller',
            'action' => 'Action',
            'description' => 'Description',
            'url_prefix' => 'URL prefix',
            'active' => 'Active',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWebpages()
    {
        return $this->hasMany(Webpage::class, ['module_id' => 'id']);
    }

    /**
     * @param $controller
     * @param string $action
     * @return null|self
     */
    public static function getModuleByControllerAndAction($controller, $action = 'view')
    {
        return self::findOne(['controller' => $controller, 'action' => $action]);
    }

    /**
     * @param $controller
     * @param string $action
     * @return int|null
     */
    public static function getModuleIdByControllerAndAction($controller, $action = 'view')
    {
        $module = self::getModuleByControllerAndAction($controller, $action);
        return $module ? $module->id : null;
    }
}
