<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ActionSearch represents the model behind the search form about `backend\models\Action`.
 */
class ActionSearch extends Action
{

    public function rules()
    {
        return [
            [['id', 'type', 'admin_id', 'user_id', 'group_id', 'amount'], 'integer'],
            [['created_at'], 'string'],
            [['comment', 'createDateString'], 'safe'],
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
        $query = Action::find()->with(['user', 'admin', 'group']);

        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 200,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                    'id' => SORT_DESC,
                ],
                'attributes' => [
                    'amount',
                    'created_at',
                    'id',
//                    'createDate',
                ],
            ],
        ];

        if ($params) {
            if (isset($params['ActionSearch'], $params['ActionSearch']['createDateString'])) {
                if ($params['ActionSearch']['createDateString']) {
                    $params['ActionSearch']['created_at'] = $params['ActionSearch']['createDateString'] . ' 00:00:00';
                }
                unset($params['ActionSearch']['createDateString']);
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
        $query->andFilterWhere([
            'type' => $this->type,
            'admin_id' => $this->admin_id,
            'user_id' => $this->user_id,
            'group_id' => $this->course_id,
        ]);

        if ($this->created_at) {
            $filterDate = new \DateTime($this->created_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'created_at', $this->created_at, $filterDate->format('Y-m-d H:i:s')]);
        }
        if (array_key_exists('amountFrom', $params) && !empty($params['amountFrom'])) {
            $query->andFilterWhere(['>=', 'amount', intval($params['amountFrom'])]);
        }
        if (array_key_exists('amountFrom', $params) && !empty($params['amountTo'])) {
            $query->andFilterWhere(['<=', 'amount', intval($params['amountTo'])]);
        }

        $query->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
