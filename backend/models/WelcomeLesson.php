<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\Course;
use common\models\traits\Inserted;
use common\models\User;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%welcome_lesson}}".
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $course_id
 * @property int         $teacher_id Учитель
 * @property int         $subject_id
 * @property string      $lesson_date
 * @property int         $status
 * @property int         $deny_reason
 * @property string      $comment
 * @property array       $comments
 * @property int         $created_by
 * @property-read User   $user
 * @property-read Course $course
 * @property DateTimeImmutable $lessonDateTime
 * @property-read string $lessonDateString
 * @property User        $createdAdmin
 */
class WelcomeLesson extends ActiveRecord
{
    use Inserted;

    public const STATUS_UNKNOWN     = 1;
    public const STATUS_PASSED      = 2;
    public const STATUS_MISSED      = 3;
    public const STATUS_CANCELED    = 4;
    public const STATUS_DENIED      = 5;
    public const STATUS_SUCCESS     = 6;
    public const STATUS_RESCHEDULED = 7;

    public const DENY_REASON_TEACHER        = 1;
    public const DENY_REASON_LEVEL_TOO_LOW  = 2;
    public const DENY_REASON_LEVEL_TOO_HIGH = 3;
    public const DENY_REASON_OTHER_GROUP    = 4;
    public const DENY_REASON_TOO_CROWDED    = 5;
    public const DENY_REASON_SUBJECT        = 6;
    public const DENY_REASON_OTHER          = 7;

    public const STATUS_LIST = [
        self::STATUS_UNKNOWN,
        self::STATUS_PASSED,
        self::STATUS_MISSED,
        self::STATUS_CANCELED,
        self::STATUS_DENIED,
        self::STATUS_SUCCESS,
        self::STATUS_RESCHEDULED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_UNKNOWN => 'Ожидается',
        self::STATUS_PASSED => 'Прошло',
        self::STATUS_MISSED => 'Студент не пришёл',
        self::STATUS_CANCELED => 'Отменено',
        self::STATUS_DENIED => 'Студент отказался ходить',
        self::STATUS_SUCCESS => 'Студент добавлен в группу',
        self::STATUS_RESCHEDULED => 'Перенесено',
    ];

    public const DENY_REASON_LIST = [
        self::DENY_REASON_TEACHER,
        self::DENY_REASON_LEVEL_TOO_LOW,
        self::DENY_REASON_LEVEL_TOO_HIGH,
        self::DENY_REASON_OTHER_GROUP,
        self::DENY_REASON_TOO_CROWDED,
        self::DENY_REASON_SUBJECT,
        self::DENY_REASON_OTHER,
    ];

    public const DENY_REASON_LABELS = [
        self::DENY_REASON_TEACHER => 'не понравился учитель',
        self::DENY_REASON_LEVEL_TOO_LOW => 'нужен уровень выше',
        self::DENY_REASON_LEVEL_TOO_HIGH => 'нужен уровень ниже',
        self::DENY_REASON_OTHER_GROUP => 'придет в другую группу',
        self::DENY_REASON_TOO_CROWDED => 'слишком большая группа',
        self::DENY_REASON_SUBJECT => 'не нужен предмет для поступления',
        self::DENY_REASON_OTHER => 'другое',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%welcome_lesson}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['comment'], 'trim'],
            [['user_id', 'course_id', 'status', 'deny_reason', 'created_by'], 'integer'],
            [['user_id', 'lesson_date'], 'required'],
            [['comment'], 'string'],
            [['deny_reason', 'comment'], 'default', 'value' => null],
            [['course_id'], 'required'],
            ['lesson_date', 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            ['status', 'in', 'range' => self::STATUS_LIST],
            ['status', 'default', 'value' => self::STATUS_UNKNOWN],
            ['deny_reason', 'in', 'range' => self::DENY_REASON_LIST],
            ['user_id', 'exist', 'targetRelation' => 'user'],
            ['course_id', 'exist', 'targetRelation' => 'course'],
            ['created_by', 'exist', 'targetRelation' => 'createdAdmin'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Студент',
            'lesson_date' => 'Дата',
            'course_id' => 'Группа',
            'deny_reason' => 'Причина отказа',
            'comment' => 'Комментарий',
            'status' => 'Статус занятия',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }

    public function getLessonDateTime(): ?DateTimeImmutable
    {
        return $this->lesson_date ? new DateTimeImmutable($this->lesson_date) : null;
    }

    public function getLessonDateString(): ?string
    {
        return $this->lessonDateTime?->format('Y-m-d');
    }

    public function setLessonDateTime(DateTimeInterface $newDate)
    {
        $this->lesson_date = $newDate->format('Y-m-d H:i:s');

        return $this;
    }

    public function getCreatedAdmin(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if ($this->isNewRecord) {
            $this->created_by = Yii::$app->user->id;
        }

        if (array_key_exists('comments', $this->oldAttributes)
            && !empty($this->oldAttributes['comments'])
            && (count($this->oldAttributes['comments']) > count($this->comments)
                || (count($this->oldAttributes['comments']) === count($this->comments) && $this->oldAttributes['comments'] != $this->comments))) {

            return false;
        }

        return true;
    }

    public function addComment(string $comment): self
    {
        $comments = $this->comments;
        $comments[] = ['text' => $comment, 'admin_id' => Yii::$app->user->id, 'date' => date('Y-m-d H:i:s')];
        $this->comments = $comments;

        return $this;
    }
}
