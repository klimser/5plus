<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%module_subject_category}}".
 *
 * @property int $id
 * @property string $name
 * @property int $webpage_id
 *
 * @property Subject[] $subjects
 * @property Subject[] $activeSubjects
 * @property Webpage $webpage
 */
class SubjectCategory extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%module_subject_category}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWebpage()
    {
        return $this->hasOne(Webpage::class, ['id' => 'webpage_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubjects()
    {
        return $this->hasMany(Subject::class, ['category_id' => 'id'])->orderBy(['name' => SORT_ASC])->inverseOf('subjectCategory');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveSubjects()
    {
        return $this->hasMany(Subject::class, ['category_id' => 'id'])->andWhere(['active' => self::STATUS_ACTIVE])->orderBy(['name' => SORT_ASC]);
    }
}
