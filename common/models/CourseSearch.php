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
            ->leftJoin(
                ['c_c' => CourseConfig::tableName()],
                [
                    'and',
                    'c.id = c_c.course_id',
                    ['<=', 'c_c.date_from', date('Y-m-d')],
                    ['or', ['c_c.date_to' => null], ['>', 'c_c.date_to', date('Y-m-d')]],
                ],
            )
            ->leftJoin(
                ['c_c_f' => CourseConfig::tableName()],
                [
                    'and',
                    'c.id = c_c_f.course_id',
                    ['>', 'c_c_f.date_from', date('Y-m-d')],
                    ['or', ['c_c_f.date_to' => null], ['>', 'c_c_f.date_to', date('Y-m-d')]],
                ],
            )
            ->leftJoin(
                ['c_c_l' => CourseConfig::tableName()],
                [
                    'and',
                    'c.id = c_c_l.course_id',
                    ['<', 'c_c_l.date_from', date('Y-m-d')],
                    ['<', 'c_c_l.date_to', date('Y-m-d')],
                ],
            )
            ->orderBy(['c_c.name' => SORT_ASC, 'c_c_f.date_from' => SORT_ASC, 'c_c_f.name' => SORT_ASC, 'c_c_l.date_to' => SORT_DESC, 'c_c_l.name' => SORT_ASC]);

        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
//                'defaultOrder' => [
//                    'active' => SORT_DESC,
//                    'name' => SORT_ASC
//                ],
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
            'and',
            ['c.id' => $this->id],
            ['subject_id' => $this->subject_id],
            ['or', 'c_c.teacher_id' => $this->teacher_id, 'c_c_f.teacher_id' => $this->teacher_id, 'c_c_l.teacher_id' => $this->teacher_id],
            ['active' => $this->active],
        ]);

        $query->andFilterWhere([
            'or',
            ['like', 'c_c.name', $this->name],
            ['like', 'c_c_f.name', $this->name],
            ['like', 'c_c_l.name', $this->name],
        ]);

        return $dataProvider;
    }
}
