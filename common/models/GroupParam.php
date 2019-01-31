<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\GroupParam as GroupParamTrait;

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
 * @property string[] $scheduleData
 * @property string $weekday
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
            [['group_id', 'year', 'month', 'lesson_price', 'weekday', 'teacher_id', 'teacher_rate'], 'required'],
            [['group_id', 'year', 'month', 'lesson_price', 'lesson_price_discount', 'teacher_id'], 'integer'],
            [['teacher_rate'], 'number'],
            [['weekday'], 'string', 'max' => 6],
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
            'lesson_price' => 'Стоимость ОДНОГО занятия',
            'lesson_price_discount' => 'Стоимость ОДНОГО занятия со скидкой',
            'weekday' => 'Расписание',
            'teacher_id' => 'Учитель',
            'teacher_rate' => 'Процент учителю',
            'teacher_salary' => 'Зарплата учителя',
        ];
    }

    public function getScheduleData()
    {
        return json_decode($this->getAttribute('schedule'), true);
    }

    public function setScheduleData($value)
    {
        $this->setAttribute('schedule', json_encode($value));
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
     * @param \DateTime $date
     * @return null|GroupParam|\yii\db\ActiveRecord
     */
    public static function findByDate(Group $group, \DateTime $date)
    {
        return self::find()->where(['group_id' => $group->id, 'year' => $date->format('Y'), 'month' => $date->format('n')])->one();
    }
}