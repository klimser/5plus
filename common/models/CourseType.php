<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%course_type}}".
 *
 * @property int      $id ID
 * @property string   $name
 *
 * @property Course[] $courses
 */
class CourseType extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%course_type}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
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
        ];
    }

    public function getCourses(): ActiveQuery
    {
        return $this->hasMany(Course::class, ['type' => 'id']);
    }
}
