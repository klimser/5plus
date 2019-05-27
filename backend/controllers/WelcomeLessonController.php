<?php

namespace backend\controllers;

use backend\models\WelcomeLesson;
use backend\models\WelcomeLessonSearch;
use common\components\ComponentContainer;
use common\components\GroupComponent;
use common\models\Group;
use common\models\Subject;
use common\models\Teacher;
use common\models\User;
use yii;
use yii\web\ForbiddenHttpException;

/**
 * WelcomeLessonController implements management for welcome lessons.
 */
class WelcomeLessonController extends AdminController
{
    const PROPOSE_GROUP_LIMIT = 6;

    /**
     * Monitor all welcome lessons.
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('welcomeLessons')) throw new ForbiddenHttpException('Access denied!');

        $searchModel = new WelcomeLessonSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $users */
        $users = User::find()->where(['id' => WelcomeLesson::find()->select(['user_id'])->distinct()->asArray()->column()])->orderBy(['name' => SORT_ASC])->all();
        $userMap = [null => 'Все'];
        foreach ($users as $user) $userMap[$user->id] = $user->name;

        /** @var Subject[] $subjects */
        $subjects = Subject::find()->orderBy('name')->all();
        $subjectMap = [null => 'Все'];
        foreach ($subjects as $subject) $subjectMap[$subject->id] = $subject->name;

        /** @var Teacher[] $teachers */
        $teachers = Teacher::find()->orderBy('name')->all();
        $teacherMap = [null => 'Все'];
        foreach ($teachers as $teacher) $teacherMap[$teacher->id] = $teacher->name;

        return $this->render('debt', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'userMap' => $userMap,
            'subjectMap' => $subjectMap,
            'teacherMap' => $teacherMap,
            'groups' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->with('teacher')->orderBy(['name' => 'ASC'])->all(),
        ]);
    }

    public function actionProposeGroup()
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');
        if (!Yii::$app->user->can('welcomeLessons')) throw new ForbiddenHttpException('Access denied!');

        $lessonId = Yii::$app->request->post('id');
        if (!$lessonId) $jsonData = self::getJsonErrorResult('Wrong request');
        else {
            $welcomeLesson = WelcomeLesson::findOne($lessonId);
            if (!$welcomeLesson) $jsonData = self::getJsonErrorResult('Welcome lesson is not found');
            else {
                $resultGroups = [];
                /** @var Group[] $groups */
                $groups = Group::find()
                    ->andWhere([
                        'active' => Group::STATUS_ACTIVE,
                        'subject_id' => $welcomeLesson->subject_id,
                        'teacher_id' => $welcomeLesson->teacher_id,
                    ])
                    ->limit(self::PROPOSE_GROUP_LIMIT)
                    ->all();
                foreach ($groups as $group) {
                    $resultGroups[] = ['id' => $group->id, 'name' => $group->name, 'teacherName' => $welcomeLesson->teacher->name];
                }
                if (empty($resultGroups)) {
                    /** @var Group[] $groups */
                    $groups = Group::find()
                        ->andWhere([
                            'active' => Group::STATUS_ACTIVE,
                            'subject_id' => $welcomeLesson->subject_id,
                        ])
                        ->limit(self::PROPOSE_GROUP_LIMIT)
                        ->with('teacher')
                        ->all();
                    foreach ($groups as $group) {
                        $resultGroups[] = ['id' => $group->id, 'name' => $group->name, 'teacherName' => $group->teacher->name];
                    }
                }
                $jsonData = self::getJsonOkResult([
                    'id' => $welcomeLesson->id,
                    'groups' => $resultGroups,
                    'pupilName' => $welcomeLesson->user->name,
                    'lessonDate' => $welcomeLesson->lessonDateTime->format('d.m.Y'),
                ]);
            }
        }

        return $this->asJson($jsonData);
    }

    public function actionMove()
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');
        if (!Yii::$app->user->can('welcomeLessons')) throw new ForbiddenHttpException('Access denied!');

        $lessonId = Yii::$app->request->post('id');
        if (!$lessonId) $jsonData = self::getJsonErrorResult('Wrong request');
        else {
            $welcomeLesson = WelcomeLesson::findOne($lessonId);
            if (!$welcomeLesson) $jsonData = self::getJsonErrorResult('Welcome lesson is not found');
            else {
                $groupProposal = Yii::$app->request->post('group_proposal');
                if ($groupProposal) {
                    $group = Group::findOne($groupProposal);
                } else {
                    $group = Group::findOne(Yii::$app->request->post('group_id'));
                }
                if (!$group || $group->active != Group::STATUS_ACTIVE) $jsonData = self::getJsonErrorResult('Group not found');
                else {
                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        GroupComponent::addPupilToGroup($welcomeLesson->user, $group, $welcomeLesson->lessonDateTime);
                        $welcomeLesson->status = WelcomeLesson::STATUS_SUCCESS;
                        $welcomeLesson->save();
                        $transaction->commit();
                        $jsonData = self::getJsonOkResult(['id' => $welcomeLesson->id]);
                } catch (\Throwable $exception) {
                        $transaction->rollBack();
                        ComponentContainer::getErrorLogger()->logError('welcome-lesson/move', $exception->getMessage(), true);
                        $jsonData = self::getJsonErrorResult('Server error');
                    }
                }
            }
        }

        return $this->asJson($jsonData);
    }
}
