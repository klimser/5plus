<?php

namespace backend\models;

use common\models\Group;
use DateTime;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * WelcomeLessonSearch represents the model behind the search form about `backend\models\WelcomeLesson`.
 */
class WelcomeLessonSearch extends WelcomeLesson
{
    public $subjectId;
    public $teacherId;
    
    public function rules()
    {
        return [
            [['group_id', 'user_id', 'status', 'deny_reason'], 'integer'],
            [['lesson_date'], 'string'],
            [['lessonDateTime', 'subjectId', 'teacherId'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'subjectId' => 'Предмет',
            'teacherId' => 'Учитель',
        ]);
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
        $query = self::find()->joinWith(['user'])->with(['user', 'group']);

        $providerParams = [
            'query' => $query,
            'pagination' => [
                'pageSize' => 200,
            ],
            'sort' => [
                'defaultOrder' => [
                    'lesson_date' => SORT_DESC,
                    'id' => SORT_DESC,
                ],
                'attributes' => [
                    'user.name',
                    'lesson_date',
                    'id',
                ],
            ],
        ];

        if ($params) {
            if (isset($params['WelcomeLessonSearch'], $params['WelcomeLessonSearch']['lessonDateString'])) {
                if ($params['WelcomeLessonSearch']['lessonDateString']) {
                    $params['WelcomeLessonSearch']['lesson_date'] = $params['WelcomeLessonSearch']['lessonDateString'] . ' 00:00:00';
                }
                unset($params['WelcomeLessonSearch']['lessonDateString']);
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
            self::tableName() . '.status' => $this->status,
            self::tableName() . '.deny_reason' => $this->deny_reason,
            self::tableName() . '.group_id' => $this->group_id,
            self::tableName() . '.user_id' => $this->user_id,
        ]);
        if (!$this->status) {
            $query->andWhere(['not', [self::tableName() . '.status' => [self::STATUS_DENIED, self::STATUS_SUCCESS]]]);
        }

        if ($this->lesson_date) {
            $filterDate = new DateTime($this->lesson_date);
            $filterDate->modify('+1 day');
            $query->andFilterWhere(['between', 'lesson_date', $this->lesson_date, $filterDate->format('Y-m-d H:i:s')]);
        }

        if ($this->subjectId) {
            $groupIds = Group::find()->andWhere(['subject_id' => $this->subjectId])->select('id')->asArray()->column();
            $query->andWhere(['group_id' => $groupIds]);
        }

        if (isset($params['WelcomeLessonSearch'], $params['WelcomeLessonSearch']['teacherId']) && !empty($params['WelcomeLessonSearch']['teacherId'])) {
            $groupIds = Group::find()->andWhere(['teacher_id' => $params['WelcomeLessonSearch']['teacherId']])->select('id')->asArray()->column();
            $query->andWhere(['group_id' => $groupIds]);
        }

        return $dataProvider;
    }
}
