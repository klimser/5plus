<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\Inserted;
use himiklab\yii2\recaptcha\ReCaptchaValidator;
use yii;

/**
 * This is the model class for table "{{%module_review}}".
 *
 * @property int $id
 * @property string $name
 * @property string $message
 * @property string $status
 * @property string $ip
 */
class Review extends ActiveRecord
{
    use Inserted;

    const STATUS_NEW = 'new';
    const STATUS_APPROVED = 'approved';

    public static $statusList = [
        self::STATUS_NEW,
        self::STATUS_APPROVED,
    ];
    public static $statusLabels = [
        self::STATUS_NEW => 'Новый',
        self::STATUS_APPROVED => 'Обработан',
    ];

    const SCENARIO_ADMIN = 'admin';
    const SCENARIO_USER = 'user';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN] = ['name', 'message', 'status'];
        $scenarios[self::SCENARIO_USER] = ['name', 'message', 'reCaptcha'];
        return $scenarios;
    }


    public static function tableName()
    {
        return '{{%module_review}}';
    }

    public $reCaptcha;


    public function rules()
    {
        return [
            [['name', 'message'], 'required'],
            [['status'], 'string'],
            [['ip'], 'string', 'max' => 40],
            [['name'], 'string', 'max' => 50],
            [['message'], 'string', 'max' => 1000],
            ['status', 'in', 'range' => self::$statusList],
            [['reCaptcha'], ReCaptchaValidator::class, 'on' => self::SCENARIO_USER],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID отзыва',
            'name' => 'Имя автора',
            'message' => 'Текст отзыва',
            'status' => 'Статус отзыва',
            'ip' => 'IP адрес отправителя',
            'created_at' => 'Дата добавления',
        ];
    }

    /**
     * @return bool
     */
    public function notifyAdmin() {
        if ($this->isNewRecord) return false;
        
        return Yii::$app->mailQueue->add('На сайте 5plus.uz оставлен отзыв, ожидает проверки!', Yii::$app->params['noticeEmail'], 'review-html', 'review-text', ['userName' => $this->name]);
    }

    /**
     * @return string
     */
    public function getCreateDateString(): string
    {
        $createDate = $this->getCreateDate();
        return $createDate ? $createDate->format('Y-m-d') : '';
    }

    /**
     * @return yii\db\ActiveQuery
     */
    public static function getActiveListQuery()
    {
        return self::find()->where(['status' => self::STATUS_APPROVED])->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * @return Subject[]
     */
    public static function getActiveList()
    {
        return self::getActiveListQuery()->all();
    }
}
