<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * DebtSearch represents the model behind the search form about `\backend\models\Debt`.
 */
class DebtSearch extends Debt
{
    public $amountFrom;
    public $amountTo;


    public function rules()
    {
        return [
            [['user_id', 'group_id', 'amount'], 'integer'],
            [['amountFrom', 'amountTo'], 'safe'],
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
        $query = Debt::find()->with(['user', 'group']);

        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 100,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_ASC,
                    'amount' => SORT_DESC,
                ],
                'attributes' => [
                    'created_at',
                    'amount',
                ],
            ],
        ];

        $dataProvider = new ActiveDataProvider($providerParams);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'user_id' => $this->user_id,
            'group_id' => $this->group_id,
        ]);

        if ($this->amountFrom) {
            $query->andFilterWhere(['>=', 'amount', intval($this->amountFrom)]);
        }
        if ($this->amountTo) {
            $query->andFilterWhere(['<=', 'amount', intval($this->amountTo)]);
        }

        return $dataProvider;
    }
}
