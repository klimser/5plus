<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * GiftCardSearch represents the model behind the search form about `backend\models\GiftCard`.
 */
class GiftCardSearch extends GiftCard
{
    public function rules()
    {
        return [
            [ [ 'amount' ], 'integer' ],
            [ [ 'name', 'customer_name', 'customer_phone', 'created_at', 'paid_at', 'used_at', 'status' ], 'string' ],
            [ [ 'created_at', 'paid_at', 'used_at' ], 'safe' ],
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
        $query = GiftCard::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 200],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'created_at' => SORT_DESC,
            ],
            'attributes' => [
                'name',
                'amount',
                'customer_name',
                'created_at',
                'paid_at',
                'used_at',
            ]
        ]);

        if ($params) {
            if (isset($params['GiftCardSearch'], $params['GiftCardSearch']['createDateString'])) {
                if ($params['GiftCardSearch']['createDateString']) {
                    $params['GiftCardSearch']['created_at'] = $params['GiftCardSearch']['createDateString'] . ' 00:00:00';
                }
                unset($params['GiftCardSearch']['createDateString']);
            }
            if (isset($params['GiftCardSearch'], $params['GiftCardSearch']['paidDateString'])) {
                if ($params['GiftCardSearch']['paidDateString']) {
                    $params['GiftCardSearch']['paid_at'] = $params['GiftCardSearch']['paidDateString'] . ' 00:00:00';
                }
                unset($params['ContractSearch']['paidDateString']);
            }
            if (isset($params['GiftCardSearch'], $params['GiftCardSearch']['usedDateString'])) {
                if ($params['GiftCardSearch']['usedDateString']) {
                    $params['GiftCardSearch']['used_at'] = $params['GiftCardSearch']['usedDateString'] . ' 00:00:00';
                }
                unset($params['ContractSearch']['usedDateString']);
            }
            if (array_key_exists('sort', $params)) {
                if ($params['sort'] == 'createDate') $params['sort'] = 'created_at';
                if ($params['sort'] == '-createDate') $params['sort'] = '-created_at';
                if ($params['sort'] == 'paidDate') $params['sort'] = 'paid_at';
                if ($params['sort'] == '-paidDate') $params['sort'] = '-paid_at';
                if ($params['sort'] == 'usedDate') $params['sort'] = 'used_at';
                if ($params['sort'] == '-usedDate') $params['sort'] = '-used_at';
            }
        }

        if (!($this->load($params) && $this->validate())) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');

            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'amount' => $this->amount,
        ]);

        if (isset($params['GiftCardSearch'], $params['GiftCardSearch']['created_at'])) {
            $filterDate = new \DateTime($this->created_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'created_at', $this->created_at, $filterDate->format('Y-m-d H:i:s')]);
        } else $this->created_at = null;
        if (isset($params['GiftCardSearch'], $params['GiftCardSearch']['paid_at'])) {
            $filterDate = new \DateTime($this->paid_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'paid_at', $this->paid_at, $filterDate->format('Y-m-d H:i:s')]);
        } else $this->paid_at = null;
        if (isset($params['GiftCardSearch'], $params['GiftCardSearch']['used_at'])) {
            $filterDate = new \DateTime($this->used_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'used_at', $this->used_at, $filterDate->format('Y-m-d H:i:s')]);
        } else $this->used_at = null;

        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['like', 'customer_name', $this->customer_name]);
        $query->andFilterWhere(['like', 'customer_phone', $this->customer_phone]);

        return $dataProvider;
    }
}
