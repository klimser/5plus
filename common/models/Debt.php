<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use common\models\traits\Inserted;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "{{%debt}}".
 *
 * @property int    $user_id
 * @property int    $course_id
 * @property double $amount
 * @property string $comment
 *
 * @property User   $user
 * @property Course $course
 */
class Debt extends ActiveRecord
{
    use Inserted;

    public static function tableName()
    {
        return '{{%debt}}';
    }


    public function rules()
    {
        return [
            [['user_id', 'course_id', 'amount'], 'required'],
            [['user_id', 'course_id'], 'integer'],
            [['amount'], 'number'],
            [['user_id', 'course_id'], 'unique', 'targetAttribute' => ['user_id', 'course_id'], 'message' => 'Debt already exists.'],
            [['user_id'], 'exist', 'targetRelation' => 'user'],
            [['course_id'], 'exist', 'targetRelation' => 'course'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'user_id' => 'Должник',
            'course_id' => 'группа',
            'amount' => 'Сумма задолженности',
            'created_at' => 'Когда появилась задолженость',
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
}