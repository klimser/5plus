<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "course_category".
 *
 * @property int $id
 * @property string $name
 */
class CourseCategory extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'course_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 50],
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
        return $this->hasMany(Course::class, ['category_id' => 'id'])
            ->inverseOf('category');
    }
}
