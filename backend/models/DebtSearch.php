<?php

namespace backend\models;

use yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * DebtSearch represents the model behind the search form about `\backend\models\Debt`.
 */
class DebtSearch extends Debt
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'danger_date'], 'integer'],
            [['amount'], 'number'],
            [['comment'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
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
        $query = Debt::find()->with('user');

        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'danger_date' => SORT_ASC,
                    'amount' => SORT_DESC,
                ],
                'attributes' => [
                    'danger_date',
                    'amount',
                ],
            ],
        ];

        if ($params) {
            if (isset($params['DebtSearch'], $params['DebtSearch']['dangerDateString'])) {
                if ($params['DebtSearch']['dangerDateString']) {
                    $params['DebtSearch']['created_at'] = $params['DebtSearch']['dangerDateString'] . ' 00:00:00';
                }
                unset($params['DebtSearch']['dangerDateString']);
            }
            if (array_key_exists('sort', $params)) {
                if ($params['sort'] == 'dangerDateString') $params['sort'] = 'created_at';
                if ($params['sort'] == '-dangerDateString') $params['sort'] = '-created_at';
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
            'user_id' => $this->user_id,
            'amount' => $this->amount,
        ]);

        if ($this->danger_date) {
            $filterDate = new \DateTime($this->danger_date);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'danger_date', $this->danger_date, $filterDate->format('Y-m-d H:i:s')]);
        }

        $query->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
