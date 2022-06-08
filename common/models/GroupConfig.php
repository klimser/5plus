<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use DateTimeImmutable;
use DateTimeInterface;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "group_config".
 *
 * @property int $id ID записи
 * @property int $group_id Группа
 * @property string $date_from применяется с
 * @property string|null $date_to применяется до
 * @property int $lesson_price Цена занятия
 * @property int|null $lesson_price_discount Цена занятия со скидкой
 * @property int $lesson_duration
 * @property array $schedule График занятий группы
 * @property int $teacher_id Учитель
 * @property float $teacher_rate Процент учителю
 * @property int|null $teacher_salary Зарплата учителя
 * @property string|null $room_number
 *
 * @property DateTimeImmutable $dateFromObject
 * @property DateTimeImmutable $dateToObject
 *
 * @property Group $group
 * @property Teacher $teacher
 */
class GroupConfig extends ActiveRecord
{
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->schedule = array_fill(0, 7, '');
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'group_config';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['group_id', 'date_from', 'lesson_price', 'lesson_duration', 'schedule', 'teacher_id', 'teacher_rate'], 'required'],
            [['group_id', 'lesson_price', 'lesson_price_discount', 'lesson_duration', 'teacher_id', 'teacher_salary'], 'integer'],
            [['date_from', 'date_to', 'schedule'], 'safe'],
            [['date_from', 'date_to'], 'date', 'format' => 'yyyy-MM-dd'],
            [['teacher_rate'], 'number'],
            [['room_number'], 'string', 'max' => 25],
            [['group_id', 'date_from', 'date_to'], 'unique', 'targetAttribute' => ['group_id', 'date_from', 'date_to']],
            [['group_id'], 'exist', 'targetRelation' => 'group'],
            [['teacher_id'], 'exist', 'targetRelation' => 'teacher'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID записи',
            'group_id' => 'Группа',
            'date_from' => 'применяется с',
            'date_to' => 'применяется до',
            'lesson_price' => 'Цена занятия',
            'lesson_price_discount' => 'Цена занятия со скидкой',
            'lesson_duration' => 'Продолжительность занятия',
            'schedule' => 'График занятий группы ',
            'teacher_id' => 'Учитель',
            'teacher_rate' => 'Процент учителю',
            'teacher_salary' => 'Зарплата учителя',
            'room_number' => 'Номер кабинета',
        ];
    }

    /**
     * Gets query for [[Group]].
     *
     * @return ActiveQuery
     */
    public function getGroup(): ActiveQuery
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * Gets query for [[Teacher]].
     *
     * @return ActiveQuery
     */
    public function getTeacher(): ActiveQuery
    {
        return $this->hasOne(Teacher::class, ['id' => 'teacher_id']);
    }

    public function setDateFromObject(?DateTimeInterface $dateFrom): void
    {
        $this->date_from = $dateFrom?->format('Y-m-d');
    }

    public function getDateFromObject(): ?DateTimeImmutable
    {
        return empty($this->date_from) ? null : new DateTimeImmutable($this->date_from . ' midnight');
    }

    public function setDateToObject(?DateTimeInterface $dateTo): void
    {
        $this->date_to = $dateTo?->format('Y-m-d');
    }

    public function getDateToObject(): ?DateTimeImmutable
    {
        return empty($this->date_to) ? null : new DateTimeImmutable($this->date_to . ' midnight');
    }

    public static function findByDate(Group $group, \DateTimeInterface $date): ?GroupConfig
    {
        $dateString = $date->format('Y-m-d');
        return GroupConfig::find()
            ->andWhere(['group_id' => $group->id])
            ->andWhere(['<=', 'date_from', $dateString])
            ->andWhere(['or', ['date_to' => null], ['>', 'date_to', $dateString]])
            ->orderBy(['date_from' => SORT_ASC, 'date_to' => SORT_ASC])
            ->one();
    }
}
