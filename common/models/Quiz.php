<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%quiz}}".
 *
 * @property string $id
 * @property string $subject_id
 * @property string $name
 * @property string $page_order
 * @property int questionCount
 *
 * @property Question[] $questions
 * @property Subject $subject
 */
class Quiz extends ActiveRecord
{
    public static $testTime = 20;
    public static $testQuestionCount = 10;
    

    public static function tableName()
    {
        return '{{%quiz}}';
    }


    public function rules()
    {
        return [
            [['subject_id', 'name'], 'required'],
            [['subject_id', 'page_order'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['subject_id'], 'exist', 'targetRelation' => 'subject'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID теста',
            'subject_id' => 'Предмет',
            'name' => 'Название теста',
            'page_order' => 'Порядок отображения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(Question::class, ['quiz_id' => 'id'])->where(['parent_id' => null])->orderBy('sort_order');
    }

    /**
     * @return int
     */
    public function getQuestionCount()
    {
        return min(self::$testQuestionCount, count($this->questions));
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubject()
    {
        return $this->hasOne(Subject::class, ['id' => 'subject_id']);
    }

    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            if ($this->questions) {
                foreach ($this->questions as $question) {
                    if (!$question->delete()) {
                        $question->moveErrorsToFlash();
                        return false;
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
