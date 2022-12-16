<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * CourseSearch represents the model behind the search form about `\backend\models\Course`.
 */
class CourseSearch extends Course
{
    public $name = null;
    public $teacher_id = null;

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
        $query = Course::find()
            ->alias('c')
            ->with(['students', 'notes'])
            ->joinWith([
                'courseConfigs c_c' => function (ActiveQuery $query) {
                    $query->andWhere(['<=', 'date_from', date('Y-m-d')])
                        ->andWhere(['or', ['date_to' => null], ['>', 'date_to', date('Y-m-d')]])
                        ->orderBy(['name' => SORT_ASC]);
                }
            ]);

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
            'c.id' => $this->id,
            'subject_id' => $this->subject_id,
            'c_c.teacher_id' => $this->teacher_id,
            'active' => $this->active,
        ]);

        $query->andFilterWhere(['like', 'c_c.name', $this->name]);

        return $dataProvider;
    }
}
