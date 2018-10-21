<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use yii;

/**
 * This is the model class for table "{{%question}}".
 *
 * @property string $id
 * @property string $quiz_id
 * @property string $parent_id
 * @property string $content
 * @property int $is_right
 * @property string $sort_order
 * @property Question rightAnswer
 * @property Question[] wrongAnswers
 *
 * @property Quiz $quiz
 * @property Question $parent
 * @property Question[] $answers
 */
class Question extends ActiveRecord
{
    /** @var  Question */
    private $_rightAnswer;
    /** @var  Question[] */
    private $_wrongAnswers;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%question}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['quiz_id', 'content'], 'required'],
            [['quiz_id', 'parent_id', 'is_right', 'sort_order'], 'integer'],
            [['content'], 'string'],
            [['quiz_id'], 'exist', 'targetRelation' => 'quiz'],
            [['parent_id'], 'exist', 'targetRelation' => 'parent'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID записи',
            'quiz_id' => 'ID теста',
            'parent_id' => 'Вопрос',
            'content' => 'Текст вопроса/ответа',
            'right_answer' => 'Правильный ответ',
            'sort_order' => 'Порядок вопроса',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuiz()
    {
        return $this->hasOne(Quiz::class, ['id' => 'quiz_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Question::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswers()
    {
        return $this->hasMany(Question::class, ['parent_id' => 'id']);
    }

    /**
     * @return Question
     */
    public function getRightAnswer()
    {
        if (!$this->_rightAnswer) {
            foreach ($this->answers as $answer) {
                if ($answer->is_right) {$this->_rightAnswer = $answer; break;}
            }
        }
        return $this->_rightAnswer;
    }

    /**
     * @return Question[]
     */
    public function getWrongAnswers()
    {
        if (!$this->_wrongAnswers) {
            $this->_wrongAnswers = [];
            foreach ($this->answers as $answer) {
                if (!$answer->is_right) $this->_wrongAnswers[] = $answer;
            }
        }
        return $this->_wrongAnswers;
    }

    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            if (!$this->parent_id) {
                foreach ($this->answers as $answer) {
                    if (!$answer->delete()) {
                        $answer->moveErrorsToFlash();
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
