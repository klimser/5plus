<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * PaymentSearch represents the model behind the search form about `backend\models\Payment`.
 */
class PaymentSearch extends Payment
{
    public $groupId;
    public $amountFrom;
    public $amountTo;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'admin_id', 'amount'], 'integer'],
            [['created_at'], 'string'],
            [['comment', 'groupId', 'amountFrom', 'amountTo'], 'safe'],
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
        $query = Payment::find()->joinWith(['groupPupil'])->with(['user', 'admin', 'groupPupil.group']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 200],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'created_at' => SORT_DESC,
                'id' => SORT_DESC,
            ],
            'attributes' => [
                'amount',
                'created_at',
                'id',
                'groupId' => [
                    'asc' => ['{{%group_pupil}}.group_id' => SORT_ASC],
                    'desc' => ['{{%group_pupil}}.group_id' => SORT_DESC],
                    'label' => 'Группа',
                ]
            ]
        ]);

        if ($params) {
            if (isset($params['PaymentSearch'], $params['PaymentSearch']['createDateString'])) {
                if ($params['PaymentSearch']['createDateString']) {
                    $params['PaymentSearch']['created_at'] = $params['PaymentSearch']['createDateString'] . ' 00:00:00';
                }
                unset($params['PaymentSearch']['createDateString']);
            }
            if (array_key_exists('sort', $params)) {
                if ($params['sort'] == 'createDate') $params['sort'] = 'created_at';
                if ($params['sort'] == '-createDate') $params['sort'] = '-created_at';
            }
        }

        if (!($this->load($params) && $this->validate())) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');

            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            '{{%payment}}.user_id' => $this->user_id,
            'admin_id' => $this->admin_id,
            'group_id' => $this->groupId,
        ]);

        if ($this->created_at) {
            $filterDate = new \DateTime($this->created_at);
            $filterDate->add(new \DateInterval('P1D'));
            $query->andFilterWhere(['between', 'created_at', $this->created_at, $filterDate->format('Y-m-d H:i:s')]);
        }
        if ($this->amountFrom) {
            $query->andFilterWhere(['>=', 'amount', intval($this->amountFrom)]);
        }
        if ($this->amountTo) {
            $query->andFilterWhere(['<=', 'amount', intval($this->amountTo)]);
        }

        $query->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
