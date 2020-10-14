<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\Group;
use common\models\traits\Inserted;
use common\models\User;
use DateTime;
use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%welcome_lesson}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int $group_id
 * @property string $lesson_date
 * @property int $status
 * @property int $deny_reason
 * @property string $comment
 * @property int $bitrix_sync_status
 * @property int $created_by
 * @property-read User $user
 * @property-read Group $group
 * @property DateTime $lessonDateTime
 * @property-read string $lessonDateString
 * @property User $createdAdmin
 */
class WelcomeLesson extends ActiveRecord
{
    use Inserted;

    public const STATUS_UNKNOWN = 1;
    public const STATUS_PASSED = 2;
    public const STATUS_MISSED = 3;
    public const STATUS_CANCELED = 4;
    public const STATUS_DENIED = 5;
    public const STATUS_SUCCESS = 6;
    public const STATUS_RESCHEDULED = 7;

    public const DENY_REASON_TEACHER = 1;
    public const DENY_REASON_LEVEL_TOO_LOW = 2;
    public const DENY_REASON_LEVEL_TOO_HIGH = 3;
    public const DENY_REASON_OTHER_GROUP = 4;
    public const DENY_REASON_TOO_CROWDED = 5;
    public const DENY_REASON_SUBJECT = 6;
    public const DENY_REASON_OTHER = 7;

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
            [['user_id', 'group_id', 'status', 'deny_reason', 'bitrix_sync_status', 'created_by'], 'integer'],
            [['user_id', 'lesson_date'], 'required'],
            [['comment'], 'string'],
            [['deny_reason', 'comment'], 'default', 'value' => null],
            [['group_id'], 'required'],
            ['lesson_date', 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            ['status', 'in', 'range' => self::STATUS_LIST],
            ['status', 'default', 'value' => self::STATUS_UNKNOWN],
            [['bitrix_sync_status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['bitrix_sync_status'], 'default', 'value' => self::STATUS_INACTIVE],
            ['deny_reason', 'in', 'range' => self::DENY_REASON_LIST],
            ['user_id', 'exist', 'targetRelation' => 'user'],
            ['group_id', 'exist', 'targetRelation' => 'group'],
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
            'group_id' => 'Группа',
            'deny_reason' => 'Причина отказа',
            'comment' => 'Комментарий',
            'status' => 'Статус занятия',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * @return DateTime|null
     */
    public function getLessonDateTime()
    {
        return $this->lesson_date ? new DateTime($this->lesson_date) : null;
    }

    /**
     * @return string|null
     */
    public function getLessonDateString()
    {
        return $this->lessonDateTime ? $this->lessonDateTime->format('Y-m-d') : null;
    }

    public function setLessonDateTime(DateTime $newDate)
    {
        $this->lesson_date = $newDate->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * @return ActiveQuery
     */
    public function getCreatedAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function beforeValidate() {
        if (!parent::beforeValidate()) {
            return false;
        }

        if ($this->isNewRecord) {
            $this->created_by = Yii::$app->user->id;
        }
        return true;
    }
}
