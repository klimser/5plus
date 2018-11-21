<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UserSearch represents the model behind the search form about `backend\models\User`.
 */
class UserSearch extends User
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'role', 'parent_id'], 'integer'],
            [['username', 'name', 'auth_key', 'password_hash', 'password_reset_token'], 'safe'],
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
        $query = User::find()->orderBy(['status' => SORT_DESC]);

        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'name' => SORT_ASC
                ],
                'attributes' => [
                    'username',
                    'name',
                    'role',
                ],
                'enableMultiSort' => true,
            ],
        ];

        $dataProvider = new ActiveDataProvider($providerParams);

        $letter = null;
        if (array_key_exists('letter', $params)) {
            if ($params['letter'] != 'ALL') $letter = $params['letter'];
            unset($params['letter']);
        }

        $year = null;
        if (array_key_exists('year', $params)) {
            if ($params['year'] > 0) $year = $params['year'];
            unset($params['year']);
        }

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if ($letter) {
            $query->andFilterWhere(['like', 'name', $letter . '%', false]);
        }

        if ($year) {
            $query->andFilterWhere(['between', 'created_at', "$year-06-01 00:00:00", ($year + 1) . '-06-01 00:00:00']);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'status' => $this->status,
            'role' => $this->role,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
