<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\GroupParam as GroupParamTrait;
use DateTime;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%group_param}}".
 *
 * @property int $id
 * @property int $group_id
 * @property int $year
 * @property int $month
 * @property-read string $valid_from
 * @property DateTime $validFromDateTime
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
            [['group_id', 'valid_from', 'lesson_price', 'teacher_id', 'teacher_rate'], 'required'],
            [['group_id', 'lesson_price', 'lesson_price_discount', 'teacher_id'], 'integer'],
            [['teacher_rate'], 'number'],
            [['valid_from'], 'date', 'format' => 'yyyy-MM-dd'],
            [['schedule'], 'string', 'max' => 255],
            [['group_id', 'valid_from'], 'unique', 'targetAttribute' => ['group_id', 'valid_from'], 'message' => 'Parameters can be set no more than 1 per day.'],
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
            'schedule' => 'График занятий группы',
            'teacher_id' => 'Учитель',
            'teacher_rate' => 'Процент учителю',
            'teacher_salary' => 'Зарплата учителя',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(Teacher::class, ['id' => 'teacher_id']);
    }

    /**
     * @return DateTime
     */
    public function getValidFromDateTime(): ?DateTime
    {
        return DateTime::createFromFormat('Y-m-d', $this->valid_from) ?: null;
    }

    /**
     * @param DateTime $validFrom
     */
    public function setValidFromDateTime(DateTime $validFrom)
    {
        $this->valid_from = $validFrom->format('Y-m-d');
    }

    /**
     * @param Group $group
     * @param DateTime $date
     * @return null|GroupParam|\yii\db\ActiveRecord
     */
    public static function findByDate(Group $group, DateTime $date)
    {
        return self::find()->where(['group_id' => $group->id, 'year' => $date->format('Y'), 'month' => $date->format('n')])->one();
    }
}
