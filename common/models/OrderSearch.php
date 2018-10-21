<?php

namespace common\models;

use yii\data\ActiveDataProvider;

/**
 * OrderSearch represents the model behind the search form about `\common\models\Order`.
 */
class OrderSearch extends Order
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['subject', 'name', 'phone', 'status', 'created_at'], 'safe'],
        ];
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
        $query = Order::find();
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
                    'subject',
                    'name',
                    'created_at',
                ],
            ],
        ];

        if ($params && isset($params['OrderSearch'], $params['OrderSearch']['createDateString'])) {
            $params['OrderSearch']['created_at'] = $params['OrderSearch']['createDateString'];
            unset($params['OrderSearch']['createDateString']);

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
            ->andFilterWhere(['like', 'subject', $this->subject])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'phone', $this->phone]);

        if ($this->created_at) {
            $filterDate = new \DateTime($this->created_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'created_at', $this->created_at . ' 00:00:00', $filterDate->format('Y-m-d H:i:s')]);
        }

        return $dataProvider;
    }
}
