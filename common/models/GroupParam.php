<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\GroupParam as GroupParamTrait;
use Yii;

/**
 * This is the model class for table "{{%group_param}}".
 *
 * @property int $id
 * @property int $group_id
 * @property int $year
 * @property int $month
 * @property int $lesson_price
 * @property int $lesson_price_discount
 * @property string $schedule
 * @property int $teacher_id
 * @property double $teacher_rate
 * @property int $teacher_salary
 *
 * @property Group $group
 * @property Teacher $teacher
 */
class GroupParam extends ActiveRecord
{
    use GroupParamTrait;

    public static function tableName()
    {
        return '{{%group_param}}';
    }


    public function rules()
    {
        return [
            [['group_id', 'year', 'month', 'lesson_price', 'teacher_id', 'teacher_rate'], 'required'],
            [['group_id', 'year', 'month', 'lesson_price', 'lesson_price_discount', 'teacher_id'], 'integer'],
            [['teacher_rate'], 'number'],
            [['schedule'], 'string', 'max' => 255],
            [['group_id', 'year', 'month'], 'unique', 'targetAttribute' => ['group_id', 'year', 'month'], 'message' => 'The combination of Группа, год and месяц has already been taken.'],
            [['group_id'], 'exist', 'targetRelation' => 'group'],
            [['teacher_id'], 'exist', 'targetRelation' => 'teacher'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID записи',
            'group_id' => 'Группа',
            'year' => 'год',
            'month' => 'месяц',
            'lesson_price' => 'Стоимость ОДНОГО занятия (< 12 занятий)',
            'lesson_price_discount' => 'Стоимость ОДНОГО занятия (> 12 занятий)',
            'schedule' => 'График занятий группы',
            'teacher_id' => 'Учитель',
            'teacher_rate' => 'Процент учителю',
            'teacher_salary' => 'Зарплата учителя',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(Teacher::class, ['id' => 'teacher_id']);
    }

    /**
     * @param Group $group
     * @param \DateTimeInterface $date
     * @return null|GroupParam|\yii\db\ActiveRecord
     */
    public static function findByDate(Group $group, \DateTimeInterface $date): ?GroupParam
    {
        return Yii::$app->cache->getOrSet(
            'groupparam.' . $group->id . '.' . $date->format('Y-n'),
            fn() => GroupParam::find()->where(['group_id' => $group->id, 'year' => $date->format('Y'), 'month' => $date->format('n')])->one(),
            3600
        );
    }
    
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        Yii::$app->cache->delete('groupparam.' . $this->group_id . '.' . $this->year . '-' . $this->month);
    }
}
