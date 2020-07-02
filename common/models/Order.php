<?php

namespace common\models;

use common\components\ComponentContainer;
use common\components\extended\ActiveRecord;
use common\models\traits\Inserted;
use common\models\traits\Phone;
use himiklab\yii2\recaptcha\ReCaptchaValidator2;
use yii;

/**
 * This is the model class for table "{{%module_order}}".
 *
 * @property string $id
 * @property string $source
 * @property string $subject
 * @property string $name
 * @property string $phone
 * @property string $tg_login
 * @property string $status
 * @property string $user_comment
 * @property string $admin_comment
 */
class Order extends ActiveRecord
{
    use Inserted, Phone;
    const STATUS_UNREAD = 'unread';
    const STATUS_READ = 'read';
    const STATUS_DONE = 'done';
    const STATUS_PROBLEM = 'problem';

    public static $statusList = [
        self::STATUS_UNREAD,
        self::STATUS_READ,
        self::STATUS_DONE,
        self::STATUS_PROBLEM,
    ];

    public static $statusLabels = [
        self::STATUS_UNREAD => 'Новый',
        self::STATUS_READ => 'Просмотрен',
        self::STATUS_DONE => 'Обработан',
        self::STATUS_PROBLEM => 'Проблемный',
    ];

    const SCENARIO_ADMIN = 'admin';
    const SCENARIO_FRONTEND = 'frontend';
    const SCENARIO_TELEGRAM = 'telegram_bot';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN] = ['username', 'name', 'phone', 'status', 'user_comment', 'admin_comment'];
        $scenarios[self::SCENARIO_FRONTEND] = ['source', 'subject', 'name', 'phone', 'phoneFormatted', 'user_comment', 'reCaptcha'];
        $scenarios[self::SCENARIO_TELEGRAM] = ['source', 'subject', 'name', 'phone', 'user_comment'];
        return $scenarios;
    }


    public static function tableName()
    {
        return '{{%module_order}}';
    }

    public $reCaptcha;


    public function rules()
    {
        return [
            [['name', 'user_comment'], 'trim'],
            [['subject', 'name', 'phone'], 'required'],
            [['status'], 'string'],
            [['subject'], 'string', 'max' => 127],
            [['tg_login'], 'string', 'max' => 32],
            [['phone'], 'string', 'max' => 50],
            [['phone'], 'match', 'pattern' => '#^\+998\d{9}$#', 'on' => [self::SCENARIO_FRONTEND]],
            [['phoneFormatted'], 'string', 'min' => 11, 'max' => 11],
            [['phoneFormatted'], 'match', 'pattern' => '#^\d{2} \d{3}-\d{4}$#'],
            [['source', 'name'], 'string', 'max' => 50],
            [['user_comment', 'admin_comment'], 'string', 'max' => 255],
            ['status', 'in', 'range' => self::$statusList],
            [['reCaptcha'], ReCaptchaValidator2::class, 'on' => self::SCENARIO_FRONTEND],
            ['source', 'default', 'value' => 'Сайт', 'on' => self::SCENARIO_FRONTEND],
            ['source', 'default', 'value' => 'Telegram бот', 'on' => self::SCENARIO_TELEGRAM],
            [['name', 'user_comment', 'tg_login'], 'default', 'value' => null],
        ];
    }


    public function attributeLabels()
    {
        $labels = [
            'id' => 'ID',
            'source' => 'Источник',
            'subject' => 'Предмет',
            'name' => 'Имя',
            'phone' => 'Номер телефона',
            'tg_login' => 'Telegram username',
            'created_at' => 'Дата подачи',
            'status' => 'Статус заявки',
            'user_comment' => 'Дополнительные сведения, пожелания',
            'admin_comment' => 'Комментарии админа',
        ];
        return $labels;
    }

    /**
     * @return bool
     */
    public function notifyAdmin() {
        if ($this->isNewRecord) return false;

        return ComponentContainer::getMailQueue()->add(
            'На сайте 5plus.uz новая заявка!',
            Yii::$app->params['noticeEmail'],
            'order-html',
            'order-text',
            ['userName' => $this->name, 'subjectName' => $this->subject]
        );
    }

    /**
     * @return string
     */
    public function getCreateDateString(): string
    {
        $createDate = $this->getCreateDate();
        return $createDate ? $createDate->format('Y-m-d') : '';
    }
}
