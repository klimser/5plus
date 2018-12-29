<?php

namespace common\models;

use common\models\traits\Inserted;
use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%quiz_result}}".
 *
 * @property string $id
 * @property string $hash
 * @property string $student_name
 * @property string $subject_name
 * @property string $quiz_name
 * @property string $questions_data
 * @property string $answers_data
 * @property array $questionsArray
 * @property array $answersArray
 * @property string $finished_at
 * @property int $rightAnswerCount
 * @property int $timeLeft
 * @property \DateTime|null $finishDate
 * @property string $timeUsed
 */
class QuizResult extends ActiveRecord
{
    use Inserted;

    private $_questionsArray = null;
    private $_answersArray = null;
    

    public static function tableName()
    {
        return '{{%quiz_result}}';
    }


    public function rules()
    {
        return [
            [['hash', 'student_name', 'questions_data', 'answers_data'], 'required'],
            [['questions_data', 'answers_data'], 'string'],
            [['subject_name'], 'string', 'max' => 50],
            [['hash', 'student_name', 'quiz_name'], 'string', 'max' => 255],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID записи',
            'hash' => 'ключ записи',
            'student_name' => 'Имя студента',
            'subject_name' => 'Название предмета',
            'quiz_name' => 'Название теста',
            'questions_data' => 'Вопросы',
            'answers_data' => 'Ответы',
            'created_at' => 'Время начала теста',
            'finished_at' => 'Время завершения теста',
        ];
    }

    /**
     * @return array
     */
    public function getQuestionsArray()
    {
        if ($this->_questionsArray === null) {
            $this->_questionsArray = json_decode($this->questions_data, true);
        }
        return $this->_questionsArray;
    }

    /**
     * @return array
     */
    public function getAnswersArray()
    {
        if ($this->_answersArray === null) {
            $this->_answersArray = json_decode($this->answers_data, true);
        }
        return $this->_answersArray;
    }

    /**
     * @return int
     */
    public function getRightAnswerCount()
    {
        $rightAnswerCount = 0;
        foreach ($this->questionsArray as $key => $question) {
            foreach ($question['answers'] as $aNum => $aContent) {
                if ($aContent[1]) {
                    if ($this->answersArray[$key] == $aNum) $rightAnswerCount++;
                    break;
                }
            }
        }
        return $rightAnswerCount;
    }

    /**
     * @return int
     */
    public function getTimeLeft()
    {
        return max($this->createDate->getTimestamp() + (60 * Quiz::TEST_TIME) - time(), 0);
    }

    /**
     * @return \DateTime
     */
    public function getFinishDate()
    {
        return empty($this->finished_at) ? null : new \DateTime($this->finished_at);
    }

    /**
     * @return string
     */
    public function getTimeUsed()
    {
        if ($this->finished_at) {
            $secondsUsed = $this->finishDate->getTimestamp() - $this->createDate->getTimestamp();
            $str = floor($secondsUsed / 60) . ' мин. ';
            if ($secondsUsed % 60 != 0) $str .= ($secondsUsed % 60) . ' сек.';
            return $str;
        } else return Quiz::TEST_TIME . ' мин.';
    }
}