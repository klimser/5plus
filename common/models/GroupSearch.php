<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * GroupSearch represents the model behind the search form about `\backend\models\Group`.
 */
class GroupSearch extends Group
{

    public function rules()
    {
        return [
            [['id', 'subject_id', 'teacher_id', 'active'], 'integer'],
            [['name'], 'safe'],
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
        $query = Group::find()->with(['pupils', 'teacher']);

        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'active' => SORT_DESC,
                    'name' => SORT_ASC
                ],
                'attributes' => [
                    'active',
                    'name',
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
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'active' => $this->active,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
