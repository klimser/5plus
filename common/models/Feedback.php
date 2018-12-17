<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\Inserted;
use himiklab\yii2\recaptcha\ReCaptchaValidator;
use yii;

/**
 * This is the model class for table "{{%module_feedback}}".
 *
 * @property string $id
 * @property string $name
 * @property string $contact
 * @property string $message
 * @property string $ip
 * @property string $status
 */
class Feedback extends ActiveRecord
{
    use Inserted;

    const PAGE_URL = 'contacts';

    const STATUS_NEW = 'new';
    const STATUS_READ = 'read';
    const STATUS_COMPLETED = 'completed';

    public static $statusList = [
        self::STATUS_NEW,
        self::STATUS_READ,
        self::STATUS_COMPLETED,
    ];
    public static $statusLabels = [
        self::STATUS_NEW => 'Новый',
        self::STATUS_READ => 'Просмотрен',
        self::STATUS_COMPLETED => 'Обработан',
    ];

    const SCENARIO_ADMIN = 'admin';
    const SCENARIO_USER = 'user';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN] = ['name', 'contact', 'message', 'status'];
        $scenarios[self::SCENARIO_USER] = ['name', 'contact', 'message', 'reCaptcha'];
        return $scenarios;
    }


    public static function tableName()
    {
        return '{{%module_feedback}}';
    }

    public $reCaptcha;


    public function rules()
    {
        return [
            [['name', 'contact', 'message'], 'required'],
            [['message', 'status'], 'string'],
            [['ip'], 'string', 'max' => 40],
            [['name'], 'string', 'max' => 50],
            [['contact'], 'string', 'max' => 255],
            ['status', 'in', 'range' => self::$statusList],
            [['reCaptcha'], ReCaptchaValidator::class, 'on' => self::SCENARIO_USER],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID сообщения',
            'name' => 'Имя',
            'contact' => 'Контактная информация',
            'message' => 'Сообщение',
            'created_at' => 'Дата добавления',
            'ip' => 'IP адрес отправителя',
            'status' => 'Статус',
        ];
    }

    /**
     * @return bool
     */
    public function notifyAdmin() {
        if ($this->isNewRecord) return false;
        
        return Yii::$app->mailQueue->add('На сайте 5plus.uz отправлено и ожидает ответа сообщение!', Yii::$app->params['noticeEmail'], 'feedback-html', 'feedback-text', ['userName' => $this->name]);
    }
}
