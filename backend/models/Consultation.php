<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\Subject;
use common\models\traits\InsertedUpdated;
use common\models\User;
use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%consultation}}".
 *
 * @property int $id
 * @property int $user_id
 * @property int $subject_id
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Subject $subject
 * @property User $user
 * @property User $createdAdmin
 */
class Consultation extends ActiveRecord
{
    use InsertedUpdated;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%consultation}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'subject_id', 'created_by'], 'required'],
            [['user_id', 'subject_id', 'created_by'], 'integer'],
            ['created_by', 'exist', 'targetRelation' => 'createdAdmin'],
            ['subject_id', 'exist', 'targetRelation' => 'subject'],
            ['user_id', 'exist', 'targetRelation' => 'user'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'subject_id' => 'Subject ID',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getSubject()
    {
        return $this->hasOne(Subject::class, ['id' => 'subject_id']);
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
