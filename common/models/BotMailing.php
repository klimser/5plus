<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\Inserted;
use Yii;
use yii\db\ActiveQuery;
use yii\web\UploadedFile;

/**
 * This is the model class for table "{{%bot_mailing}}".
 *
 * @property int $id
 * @property int $admin_id
 * @property string $message_text
 * @property string $message_image
 * @property array $process_result
 * @property array $usersResult
 * @property string $created_at
 * @property string $started_at
 * @property \DateTime|null $startDate
 * @property string $finished_at
 *
 * @property User $admin
 */
class BotMailing extends ActiveRecord
{
    use Inserted;

    /** @var UploadedFile */
    public $imageFile;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%bot_mailing}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['admin_id'], 'required'],
            [['admin_id'], 'integer'],
            [['message_text'], 'string'],
            [['message_text'], 'string'],
            [['created_at', 'started_at', 'finished_at'], 'safe'],
            [['message_image'], 'string', 'max' => 255],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg', 'checkExtensionByMimeType' => true, 'maxSize' => 5242880, 'tooBig' => 'Файл должен быть не более 5МБ'],
            [['admin_id'], 'exist', 'targetRelation' => 'admin', 'filter' => ['role' => [User::ROLE_ROOT, User::ROLE_MANAGER]]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'admin_id' => 'Автор',
            'message_text' => 'Сообщение',
            'message_image' => 'Картинка',
            'process_result' => 'Результат',
            'created_at' => 'Рассылка создана',
            'started_at' => 'Начало рассылки',
            'finished_at' => 'Рассылка завершена',
            'imageFile' => 'Картинка',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }
    
    public function getUsersResult(): array
    {
        if ($this->process_result) {
            $chatIds = !empty($this->process_result['userResult']) ? array_keys($this->process_result['userResult']) : [];
            /** @var User[] $users */
            $users = User::find()->andWhere(['tg_chat_id' => $chatIds])->all();
            $result = [];
            foreach ($users as $user) {
                $result[] = ['user' => $user, 'result' => $this->process_result['userResult'][$user->tg_chat_id]];
            }
            return $result;
        }
        
        return [];
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return empty($this->started_at) ? null : new \DateTime($this->started_at);
    }
    
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->message_text = str_replace('<br>', "\n", $this->message_text);
            $this->message_text = str_replace('&nbsp;', ' ', $this->message_text);
            if ($this->isNewRecord) {
                $this->admin_id = Yii::$app->user->id;
                if ($this->started_at && !preg_match('#^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$#', $this->started_at)) {
                    $startDate = date_create_from_format('d.m.Y H:i', $this->started_at);
                    $this->started_at = $startDate->format('Y-m-d H:i:s');
                }
            }
            return true;
        }
        
        return false;
    }
}
