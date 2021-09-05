<?php

namespace backend\controllers;

use common\models\Company;
use common\models\Group;
use common\models\Teacher;
use yii;
use common\models\Subject;

/**
 * AjaxInfoController returns infos via ajax-requests
 */
class AjaxInfoController extends AdminController
{
    public function beforeAction($action)
    {
        if (!Yii::$app->request->isAjax) {
            return false;
        }
        return parent::beforeAction($action);
    }

    private function getFilter(): array
    {
        return Yii::$app->request->get('filter', []);
    }

    private function getOrder($defaultOrder = null): array
    {
        $order = Yii::$app->request->get('order', $defaultOrder);
        if ($order) {
            $field = preg_replace('#^-#', '', $order);
            $direction = mb_substr($order, 0, 1) === '-' ? SORT_DESC : SORT_ASC;
            return [$field => $direction];
        }
        return [];
    }

    public function actionSubjects()
    {
        $where = [];
        $params = [];
        $whitelist = ['name', 'active'];
        foreach ($this->getFilter() as $key => $value) {
            if (in_array($key, $whitelist)) {
                $where[] = Subject::tableName() . ".$key = :$key";
                $params[":$key"] = $value;
            }
        }
        $query = Subject::find();
        foreach ($where as $condition) {
            $query->andWhere($condition);
        }
        /** @var Subject[] $subjects */
        $subjects = $query->addParams($params)
            ->orderBy($this->getOrder('name'))
            ->with('subjectCategory')
            ->all();
        $resultArray = [];
        foreach ($subjects as $subject) {
            $resultArray[] = [
                'id' => $subject->id,
                'name' => $subject->name,
                'categoryId' => $subject->category_id,
                'category' => $subject->subjectCategory->name,
            ];
        }

        return $this->asJson($resultArray);
    }
    
    public function actionGroups()
    {
        $where = [];
        $params = [];
        $whitelist = ['name', 'active', 'subject_id', 'teacher_id'];
        foreach ($this->getFilter() as $key => $value) {
            if (in_array($key, $whitelist)) {
                $where[] = Group::tableName() . ".$key = :$key";
                $params[":$key"] = $value;
            }
        }
        $query = Group::find();
        foreach ($where as $condition) {
            $query->andWhere($condition);
        }
        /** @var Group[] $groups */
        $groups = $query->addParams($params)
            ->orderBy($this->getOrder('name'))
            ->with('subject', 'teacher')
            ->all();
        $resultArray = [];
        foreach ($groups as $group) {
            $resultArray[] = [
                'id' => $group->id,
                'name' => $group->name,
                'active' => $group->active === Group::STATUS_ACTIVE,
                'subjectId' => $group->subject_id,
                'subject' => $group->subject->name,
                'teacherId' => $group->teacher_id,
                'teacher' => $group->teacher->name,
                'priceLesson' => $group->lesson_price,
                'price12Lesson' => $group->price12Lesson,
                'dateStart' => $group->date_start,
                'dateEnd' => $group->date_end,
                'weekDays' => array_map(function($val) { return ($val + 1) % 7; }, array_keys(array_filter($group->scheduleData))),
            ];
        }

        return $this->asJson($resultArray);
    }

    public function actionTeachers()
    {
        $where = [];
        $params = [];
        $whitelist = ['name', 'active', 'subject_id'];
        foreach ($this->getFilter() as $key => $value) {
            if (in_array($key, $whitelist)) {
                $where[] = Teacher::tableName() . ".$key = :$key";
                $params[":$key"] = $value;
            }
        }
        $query = Teacher::find()
            ->innerJoinWith('teacherSubjects');
        foreach ($where as $condition) {
            $query->andWhere($condition);
        }
        /** @var Teacher[] $teachers */
        $teachers = $query->addParams($params)
            ->orderBy($this->getOrder(Teacher::tableName() . '.name'))
            ->with('teacherSubjects')
            ->all();
        $resultArray = [];
        foreach ($teachers as $teacher) {
            $resultArray[] = [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'subjectIds' => yii\helpers\ArrayHelper::getColumn($teacher->teacherSubjects, 'subject_id'),
            ];
        }

        return $this->asJson($resultArray);
    }
}
