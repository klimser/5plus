<?php

namespace backend\models;

use common\components\extended\ActiveRecord;
use common\models\Course;
use common\models\traits\Inserted;
use common\models\User;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%action}}".
 *
 * @property int    $id
 * @property int    $type
 * @property int    $admin_id
 * @property int    $user_id
 * @property int    $course_id
 * @property int    $amount
 * @property string $comment
 *
 * @property User   $admin
 * @property User   $user
 * @property Course $course
 */
class Action extends ActiveRecord
{
    use Inserted;


    public static function tableName()
    {
        return '{{%action}}';
    }


    public function rules()
    {
        return [
            [['type'], 'required'],
            [['type', 'admin_id', 'user_id', 'course_id', 'amount'], 'integer'],
            [['comment'], 'string'],
            [['admin_id'], 'exist', 'targetRelation' => 'admin'],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
            [['course_id'], 'exist', 'targetRelation' => 'course'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID записи',
            'type' => 'Тип записи',
            'admin_id' => 'Админ',
            'user_id' => 'Клиент',
            'course_id' => 'Группа',
            'amount' => 'Сумма',
            'comment' => 'Комментарий',
            'created_at' => 'Дата операции',
        ];
    }

    public function getAdmin(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'admin_id']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getCourse(): ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'course_id']);
    }
}
