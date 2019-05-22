<?php

namespace backend\controllers;

use backend\models\WelcomeLesson;
use backend\models\WelcomeLessonSearch;
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
    /**
     * Monitor all welcome lessons.
     * @param int $status
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionIndex(int $status = -1)
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
        ]);
    }
}
