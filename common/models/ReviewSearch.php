<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ReviewSearch represents the model behind the search form about `\common\models\Review`.
 */
class ReviewSearch extends Review
{

    public function rules()
    {
        return [
            [['name', 'status', 'created_at'], 'safe'],
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
        $query = Review::find();
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
                    'name',
                    'created_at',
                    'status',
                ],
            ],
        ];

        if ($params && isset($params['ReviewSearch'], $params['ReviewSearch']['createDateString'])) {
            $params['ReviewSearch']['created_at'] = $params['ReviewSearch']['createDateString'];
            unset($params['ReviewSearch']['createDateString']);

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


        $query->andFilterWhere(['status' => $this->status,])
            ->andFilterWhere(['like', 'name', $this->name]);

        if ($this->created_at) {
            $filterDate = new \DateTime($this->created_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'created_at', $this->created_at . ' 00:00:00', $filterDate->format('Y-m-d H:i:s')]);
        }

        return $dataProvider;
    }
}
