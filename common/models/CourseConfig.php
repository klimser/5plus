<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\components\helpers\MoneyHelper;
use DateTimeImmutable;
use DateTimeInterface;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "course_config".
 *
 * @property int $id ID записи
 * @property int $course_id Группа
 * @property string $name
 * @property string $legal_name
 * @property string $date_from применяется с
 * @property string|null $date_to применяется до
 * @property int $lesson_price Цена занятия
 * @property int|null $lesson_price_discount Цена занятия со скидкой
 * @property int               $lesson_duration Длительность занятия
 * @property array             $schedule График занятий группы
 * @property int               $teacher_id Учитель
 * @property float             $teacher_rate Процент учителю
 * @property int               $teacher_lesson_pay Фиксированная оплата учителю за урок
 * @property string|null       $room_number
 *
 * @property DateTimeImmutable|null $dateFromObject
 * @property DateTimeImmutable|null $dateToObject
 * @property int               $priceMonth
 * @property int               $price12Lesson
 * @property int               $classesPerWeek
 * @property int               $classesPerMonth
 *
 * @property Course            $course
 * @property Teacher           $teacher
 */
class CourseConfig extends ActiveRecord
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
        return '{{%course_config}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name', 'legal_name', 'room_number'], 'trim'],
            [['name', 'legal_name', 'course_id', 'date_from', 'lesson_price', 'lesson_duration', 'schedule', 'teacher_id'], 'required'],
            [['course_id', 'lesson_price', 'lesson_price_discount', 'lesson_duration', 'teacher_id', 'teacher_lesson_pay'], 'integer'],
            [['date_from', 'date_to', 'schedule'], 'safe'],
            [['date_from', 'date_to'], 'date', 'format' => 'yyyy-MM-dd'],
            [['teacher_rate'], 'number', 'min' => 0, 'max' => 100],
            [['room_number'], 'string', 'max' => 25],
            [['name', 'legal_name'], 'string', 'min' => 3, 'max' => 100],
            [['course_id', 'date_from', 'date_to'], 'unique', 'targetAttribute' => ['course_id', 'date_from', 'date_to']],
            [['course_id'], 'exist', 'targetRelation' => 'course'],
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
            'course_id' => 'Группа',
            'name' => 'Название группы',
            'legal_name' => 'Официальное название (договор, сайт, бот)',
            'date_from' => 'применяется с',
            'date_to' => 'применяется до',
            'lesson_price' => 'Цена занятия',
            'lesson_price_discount' => 'Цена занятия со скидкой',
            'lesson_duration' => 'Продолжительность занятия',
            'schedule' => 'График занятий группы ',
            'teacher_id' => 'Учитель',
            'teacher_rate' => 'Процент учителю',
            'teacher_lesson_pay' => 'Фиксированная оплата учителю за занятие',
            'room_number' => 'Номер кабинета',
        ];
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }

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

    public static function findByDate(Course $course, DateTimeInterface $date): ?CourseConfig
    {
        $dateString = $date->format('Y-m-d');

        return CourseConfig::find()
            ->andWhere(['course_id' => $course->id])
            ->andWhere(['<=', 'date_from', $dateString])
            ->andWhere(['or', ['date_to' => null], ['>', 'date_to', $dateString]])
            ->one();
    }

    public function hasLesson(DateTimeInterface $day): bool
    {
        return
            $day >= $this->dateFromObject
            && (empty($this->date_to) || $day < $this->dateToObject)
            && !empty($this->schedule[(7 + intval($day->format('w')) - 1) % 7]);
    }

    public function getLessonTime(DateTimeInterface $day): string
    {
        if (!$this->hasLesson($day)) {
            throw new \Exception(sprintf('No lesson that day: %s', $day->format('Y-m-d')));
        }

        return $this->schedule[(7 + intval($day->format('w')) - 1) % 7] . ':00';
    }

    public function getLessonDateTime(DateTimeInterface $day): string
    {
        if (!$this->hasLesson($day)) {
            throw new \Exception(sprintf('No lesson that day: %s', $day->format('Y-m-d')));
        }

        return $day->format('Y-m-d') . ' ' . $this->getLessonTime($day);
    }

    public function getPrice12Lesson(): int
    {
        return MoneyHelper::roundTen(($this->lesson_price_discount ?: $this->lesson_price) * 12);
    }

    public function getPriceMonth(): int
    {
        $perMonth = $this->getClassesPerMonth();

        return MoneyHelper::roundTen($perMonth * ($perMonth >= 12 ? $this->lesson_price_discount : $this->lesson_price));
    }

    public function getClassesPerWeek(): int
    {
        $count = 0;
        foreach ($this->schedule as $elem) {
            if (!empty($elem)) {
                $count++;
            }
        }

        return $count;
    }

    public function getClassesPerMonth(): int
    {
        return $this->getClassesPerWeek() * 4;
    }
}
