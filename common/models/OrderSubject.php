<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use yii;

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

    public static function getAllSubjects()
    {
        $subjectMap = Yii::$app->cache->get('order_subject.all');
        if (!$subjectMap) {
            $subjectMap = [];
            $dataArray = OrderSubject::find()->asArray('name')->all();
            foreach ($dataArray as $subject) {
                $subjectMap[$subject['name']] = $subject['name'];
            }
            Yii::$app->cache->set('subject.all', $subjectMap);
        }
        return $subjectMap;
    }

    public static function clearAllCache()
    {
        return Yii::$app->cache->delete('order_subject.all');
    }
}
