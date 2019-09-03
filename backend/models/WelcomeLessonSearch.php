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
            [['subject_id', 'teacher_id', 'group_id', 'user_id', 'status'], 'integer'],
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
            if (isset($params['WelcomeLessonSearch'], $params['WelcomeLessonSearch']['lessonDateTime'])) {
                if ($params['WelcomeLessonSearch']['lessonDateTime']) {
                    $params['WelcomeLessonSearch']['lesson_date'] = $params['WelcomeLessonSearch']['lessonDateTime'] . ' 00:00:00';
                }
                unset($params['WelcomeLessonSearch']['lessonDateTime']);
            }
            if (array_key_exists('sort', $params)) {
                if ($params['sort'] == 'lessonDateTime') $params['sort'] = 'lesson_date';
                if ($params['sort'] == '-lessonDateTime') $params['sort'] = '-lesson_date';
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
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'group_id' => $this->group_id,
            'user_id' => $this->user_id,
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
