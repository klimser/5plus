<?php

namespace backend\models;

use common\models\Course;
use common\models\CourseConfig;
use DateTime;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

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
            [['course_id', 'user_id', 'status', 'deny_reason'], 'integer'],
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
        $query = self::find()->alias('wl')->joinWith(['user'])->with(['user']);

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
            'wl.status' => $this->status,
            'wl.deny_reason' => $this->deny_reason,
            'wl.course_id' => $this->course_id,
            'wl.user_id' => $this->user_id,
        ]);
        if (!$this->status) {
            $query->andWhere(['not', ['wl.status' => [self::STATUS_DENIED, self::STATUS_SUCCESS]]]);
        }

        if ($this->lesson_date) {
            $filterDate = new DateTime($this->lesson_date);
            $filterDate->modify('+1 day');
            $query->andFilterWhere(['between', 'wl.lesson_date', $this->lesson_date, $filterDate->format('Y-m-d H:i:s')]);
        }

        if ($this->subjectId) {
            $courseIds = Course::find()->andWhere(['subject_id' => $this->subjectId])->select('id')->asArray()->column();
            $query->andWhere(['wl.course_id' => $courseIds]);
        }

        if (isset($params['WelcomeLessonSearch'], $params['WelcomeLessonSearch']['teacherId']) && !empty($params['WelcomeLessonSearch']['teacherId'])) {
            $query->leftJoin(
                CourseConfig::tableName() . ' c_c',
                'c_c.course_id = wl.course_id AND c_c.date_from <= DATE(wl.lesson_date) AND (c_c.date_to IS NULL OR c_c.date_to > DATE(wl.lesson_date))'
            )
                ->andWhere(['c_c.teacher_id' => $params['WelcomeLessonSearch']['teacherId']]);
        }

        return $dataProvider;
    }
}
