<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * QuizResultSearch represents the model behind the search form about `\common\models\QuizResult`.
 */
class QuizResultSearch extends QuizResult
{

    public function rules()
    {
        return [
            [['created_at'], 'integer'],
            [['hash', 'student_name', 'subject_name', 'quiz_name', 'createDate'], 'safe'],
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
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = QuizResult::find();
        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
                'attributes' => [
                    'student_name',
                    'subject_name',
                    'created_at',
                ],
            ],
        ];

        if ($params && isset($params['QuizResultSearch'], $params['QuizResultSearch']['createDate'])) {
            if (isset($params['QuizResultSearch'], $params['QuizResultSearch']['createDateString'])) {
                if ($params['QuizResultSearch']['createDateString']) {
                    $params['QuizResultSearch']['created_at'] = $params['QuizResultSearch']['createDateString'] . ' 00:00:00';
                }
                unset($params['QuizResultSearch']['createDateString']);
            }
            if (array_key_exists('sort', $params)) {
                if ($params['sort'] == 'createDateString') $params['sort'] = 'created_at';
                if ($params['sort'] == '-createDateString') $params['sort'] = '-created_at';
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
        $query->andFilterWhere(['like', 'student_name', $this->student_name])
            ->andFilterWhere(['like', 'subject_name', $this->subject_name])
            ->andFilterWhere(['like', 'quiz_name', $this->quiz_name]);

        if ($this->created_at) {
            $filterDate = new \DateTime($this->created_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'created_at', $this->created_at, $filterDate->format('Y-m-d H:i:s')]);
        }

        return $dataProvider;
    }
}
