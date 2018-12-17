<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * FeedbackSearch represents the model behind the search form about `\common\models\Feedback`.
 */
class FeedbackSearch extends Feedback
{

    public function rules()
    {
        return [
            [['name', 'contact', 'message', 'status', 'created_at'], 'safe'],
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
        $query = Feedback::find();
        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
                'attributes' => [
                    'status',
                    'name',
                    'created_at',
                ],
            ],
        ];

        if ($params && isset($params['FeedbackSearch'], $params['FeedbackSearch']['createDateString'])) {
            $params['FeedbackSearch']['created_at'] = $params['FeedbackSearch']['createDateString'];
            unset($params['FeedbackSearch']['createDateString']);

            if (array_key_exists('sort', $params)) {
                if ($params['sort'] == 'createDate') $params['sort'] = 'created_at';
                if ($params['sort'] == '-createDate') $params['sort'] = '-created_at';
            }
        }

        $dataProvider = new ActiveDataProvider($providerParams);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['status' => $this->status])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'contact', $this->contact]);

        if ($this->created_at) {
            $filterDate = new \DateTime($this->created_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'created_at', $this->created_at . ' 00:00:00', $filterDate->format('Y-m-d H:i:s')]);
        }

        return $dataProvider;
    }
}
