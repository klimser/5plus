<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ContractSearch represents the model behind the search form about `backend\models\Contract`.
 */
class ContractSearch extends Contract
{
    public $amountFrom;
    public $amountTo;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'group_id', 'amount'], 'integer'],
            [['created_at', 'paid_at', 'number'], 'string'],
            [['amountFrom', 'amountTo'], 'safe'],
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
        $query = Contract::find()->with(['user', 'group']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 200],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'created_at' => SORT_DESC,
                'number' => SORT_DESC,
            ],
            'attributes' => [
                'amount',
                'created_at',
                'paid_at',
                'number',
            ]
        ]);

        if ($params) {
            if (isset($params['ContractSearch'], $params['ContractSearch']['createDateString'])) {
                if ($params['ContractSearch']['createDateString']) {
                    $params['ContractSearch']['created_at'] = $params['ContractSearch']['createDateString'] . ' 00:00:00';
                }
                unset($params['ContractSearch']['createDateString']);
            }
            if (isset($params['ContractSearch'], $params['ContractSearch']['paidDateString'])) {
                if ($params['ContractSearch']['paidDateString']) {
                    $params['ContractSearch']['paid_at'] = $params['ContractSearch']['paidDateString'] . ' 00:00:00';
                }
                unset($params['ContractSearch']['paidDateString']);
            }
            if (array_key_exists('sort', $params)) {
                if ($params['sort'] == 'createDate') $params['sort'] = 'created_at';
                if ($params['sort'] == '-createDate') $params['sort'] = '-created_at';
                if ($params['sort'] == 'paidDate') $params['sort'] = 'paid_at';
                if ($params['sort'] == '-paidDate') $params['sort'] = '-paid_at';
            }
        }

        if (!($this->load($params) && $this->validate())) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');

            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'user_id' => $this->user_id,
            'group_id' => $this->group_id,
        ]);

        if (isset($params['ContractSearch'], $params['ContractSearch']['created_at'])) {
            $filterDate = new \DateTime($this->created_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'created_at', $this->created_at, $filterDate->format('Y-m-d H:i:s')]);
        } else $this->created_at = null;
        if (isset($params['ContractSearch'], $params['ContractSearch']['paid_at'])) {
            $filterDate = new \DateTime($this->paid_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'paid_at', $this->paid_at, $filterDate->format('Y-m-d H:i:s')]);
        } else $this->paid_at = null;
        if ($this->amountFrom) {
            $query->andFilterWhere(['>=', 'amount', intval($this->amountFrom)]);
        }
        if ($this->amountTo) {
            $query->andFilterWhere(['<=', 'amount', intval($this->amountTo)]);
        }

        $query->andFilterWhere(['like', 'number', $this->number]);

        return $dataProvider;
    }
}
