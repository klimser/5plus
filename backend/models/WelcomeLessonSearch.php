<?php

namespace backend\models;

use DateTime;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * WelcomeLessonSearch represents the model behind the search form about `backend\models\WelcomeLesson`.
 */
class WelcomeLessonSearch extends WelcomeLesson
{
    public function rules()
    {
        return [
            [['subject_id', 'teacher_id', 'group_id', 'user_id', 'status', 'deny_reason'], 'integer'],
            [['lesson_date'], 'string'],
            [['lessonDateTime'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = self::find()->joinWith(['user'])->with(['user', 'subject', 'teacher', 'group']);

        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 200,
            ],
            'sort' => [
                'defaultOrder' => [
                    'lesson_date' => SORT_DESC,
                    'id' => SORT_DESC,
                ],
                'attributes' => [
                    'user.name',
                    'lesson_date',
                    'id',
                ],
            ],
        ];

        if ($params) {
            if (isset($params['WelcomeLessonSearch'], $params['WelcomeLessonSearch']['lessonDateString'])) {
                if ($params['WelcomeLessonSearch']['lessonDateString']) {
                    $params['WelcomeLessonSearch']['lesson_date'] = $params['WelcomeLessonSearch']['lessonDateString'] . ' 00:00:00';
                }
                unset($params['WelcomeLessonSearch']['lessonDateTimeString']);
            }
        }

        $dataProvider = new ActiveDataProvider($providerParams);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            self::tableName() . '.status' => $this->status,
            self::tableName() . '.deny_reason' => $this->deny_reason,
            self::tableName() . '.subject_id' => $this->subject_id,
            self::tableName() . '.teacher_id' => $this->teacher_id,
            self::tableName() . '.group_id' => $this->group_id,
            self::tableName() . '.user_id' => $this->user_id,
        ]);
        if (!$this->status) {
            $query->andWhere(['not', [self::tableName() . '.status' => [self::STATUS_DENIED, self::STATUS_SUCCESS]]]);
        }

        if ($this->lesson_date) {
            $filterDate = new DateTime($this->lesson_date);
            $filterDate->modify('+1 day');
            $query->andFilterWhere(['between', 'lesson_date', $this->lesson_date, $filterDate->format('Y-m-d H:i:s')]);
        }

        return $dataProvider;
    }
}
