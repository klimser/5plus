<?php

namespace backend\controllers;

use backend\models\WelcomeLesson;
use backend\models\WelcomeLessonSearch;
use common\components\ComponentContainer;
use common\components\GroupComponent;
use common\components\MoneyComponent;
use common\models\Group;
use common\models\Subject;
use common\models\Teacher;
use common\models\User;
use common\resources\documents\WelcomeLessons;
use yii;
use yii\web\Response;

/**
 * WelcomeLessonController implements management for welcome lessons.
 */
class WelcomeLessonController extends AdminController
{
    const PROPOSE_GROUP_LIMIT = 6;

    protected $accessRule = 'welcomeLessons';

    public function actionPrint(array $id)
    {
        $welcomeLessons = WelcomeLesson::find()->andWhere(['id' => $id])->all();
        $doc = new WelcomeLessons($welcomeLessons);
        return Yii::$app->response->sendContentAsFile(
            $doc->save(),
            'doc.pdf',
            ['inline' => true, 'mimeType' => 'application/pdf']
        );
    }
    
    /**
     * Monitor all welcome lessons.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new WelcomeLessonSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var User[] $users */
        $users = User::find()->where(['id' => WelcomeLesson::find()->select(['user_id'])->distinct()->asArray()->column()])->orderBy(['name' => SORT_ASC])->all();
        $studentMap = [null => 'Все'];
        foreach ($users as $user) $studentMap[$user->id] = $user->name;

        /** @var Group[] $groups */
        $groups = Group::find()->where(['id' => WelcomeLesson::find()->select(['group_id'])->distinct()->asArray()->column()])->orderBy(['name' => SORT_ASC])->all();
        $groupMap = [null => 'Все'];
        foreach ($groups as $group) $groupMap[$group->id] = $group->name;

        /** @var Subject[] $subjects */
        $subjects = Subject::find()->orderBy('name')->all();
        $subjectMap = [null => 'Все'];
        foreach ($subjects as $subject) $subjectMap[$subject->id] = $subject->name;

        /** @var Teacher[] $teachers */
        $teachers = Teacher::find()->andWhere(['active' => Teacher::STATUS_ACTIVE])->orderBy('name')->all();
        $teacherMap = [null => 'Все'];
        foreach ($teachers as $teacher) $teacherMap[$teacher->id] = $teacher->name;

        $statusMap = [null => 'Активные'];
        foreach (WelcomeLesson::STATUS_LABELS as $value => $label) {
            $statusMap[$value] = $label;
        }

        $reasonsMap = [null => 'Все'];
        foreach (WelcomeLesson::DENY_REASON_LABELS as $value => $label) {
            $reasonsMap[$value] = $label;
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'studentMap' => $studentMap,
            'subjectMap' => $subjectMap,
            'teacherMap' => $teacherMap,
            'groupMap' => $groupMap,
            'statusMap' => $statusMap,
            'reasonsMap' => $reasonsMap,
            'groups' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->with('teacher')->orderBy(['name' => 'ASC'])->all(),
        ]);
    }

    private function getAjaxInfoResult(WelcomeLesson $welcomeLesson)
    {
        return self::getJsonOkResult([
            'result' => [
                'id' => $welcomeLesson->id,
                'date' => $welcomeLesson->lessonDateTime->format('d.m.Y'),
                'status' => $welcomeLesson->status,
                'denyReason' => $welcomeLesson->deny_reason,
            ],
        ]);
    }

    /**
     * @param $id
     * @return mixed
     * @throws yii\web\NotFoundHttpException
     */
    public function actionChangeStatus($id)
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $welcomeLesson = $this->findModel($id);

        $newStatus = intval(Yii::$app->request->post('status'));
        if ($welcomeLesson->status !== WelcomeLesson::STATUS_UNKNOWN
            && ($welcomeLesson->status !== WelcomeLesson::STATUS_PASSED
                || !in_array($newStatus, [WelcomeLesson::STATUS_MISSED, WelcomeLesson::STATUS_DENIED]))) {
            return self::getJsonErrorResult(
                'Статус "' . WelcomeLesson::STATUS_LABELS[$newStatus] . '" не может быть установлен занятию со статусом "' . WelcomeLesson::STATUS_LABELS[$welcomeLesson->status] . '"'
            );
        }
        $welcomeLesson->status = $newStatus;
        $welcomeLesson->bitrix_sync_status = WelcomeLesson::STATUS_INACTIVE;

        if (!$welcomeLesson->save()) {
            return self::getJsonErrorResult($welcomeLesson->getErrorsAsString('status'));
        }

        return $this->getAjaxInfoResult($welcomeLesson);
    }
    
    public function actionSetDenyDetails($id)
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $welcomeLesson = $this->findModel($id);

        if ($welcomeLesson->status !== WelcomeLesson::STATUS_DENIED) {
            return self::getJsonErrorResult(
                'Причина может быть установлена только в статусе "' . WelcomeLesson::STATUS_LABELS[WelcomeLesson::STATUS_DENIED] . '"'
            );
        }

        $welcomeLesson->deny_reason = intval(Yii::$app->request->post('deny_reason'));
        $welcomeLesson->comment = Yii::$app->request->post('comment');
        if (!$welcomeLesson->save()) {
            return self::getJsonErrorResult($welcomeLesson->getErrorsAsString());
        }

        return $this->getAjaxInfoResult($welcomeLesson);
    }

    public function actionProposeGroup()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $lessonId = Yii::$app->request->post('id');
        if (!$lessonId) return self::getJsonErrorResult('Wrong request');
        
        $welcomeLesson = WelcomeLesson::findOne($lessonId);
        if (!$welcomeLesson) return self::getJsonErrorResult('Welcome lesson is not found');
        
        $resultGroupIds = $excludeGroupIds = [];
        if ($welcomeLesson->group_id) {
            $resultGroupIds[] = $welcomeLesson->group_id;
        } else {
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
                $resultGroupIds[] = $group->id;
            }
        }
        
        if (empty($resultGroupIds)) {
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
                $resultGroupIds[] = $group->id;
            }
        }
        
        foreach ($welcomeLesson->user->activeGroupPupils as $groupPupil) {
            $excludeGroupIds[] = $groupPupil->group_id;
        }
        
        return self::getJsonOkResult([
            'id' => $welcomeLesson->id,
            'groupIds' => $resultGroupIds,
            'excludeGroupIds' => $excludeGroupIds,
            'pupilName' => $welcomeLesson->user->name,
            'lessonDate' => $welcomeLesson->lessonDateTime->format('d.m.Y'),
        ]);
    }

    public function actionReschedule()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $welcomeLessonData = Yii::$app->request->post('welcome_lesson', []);
        if (!isset($welcomeLessonData['id'], $welcomeLessonData['date'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        if (!$welcomeLesson = WelcomeLesson::findOne($welcomeLessonData['id'])) {
            return self::getJsonErrorResult('Welcome lesson is not found');
        }

        $startDate = new \DateTime($welcomeLessonData['date']);
        if (!$startDate) {
            return self::getJsonErrorResult('Wrong lesson date');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $welcomeLesson->status = WelcomeLesson::STATUS_RESCHEDULED;
            $welcomeLesson->save();

            $newWelcomeLesson = new WelcomeLesson();
            $newWelcomeLesson->group_id = $welcomeLesson->group_id;
            $newWelcomeLesson->subject_id = $welcomeLesson->subject_id;
            $newWelcomeLesson->teacher_id = $welcomeLesson->teacher_id;
            $newWelcomeLesson->user_id = $welcomeLesson->user_id;
            $newWelcomeLesson->lessonDateTime = $startDate;

            if (!$newWelcomeLesson->save()) {
                throw new \Exception('Server error: ' . $newWelcomeLesson->getErrorsAsString());
            }

            $transaction->commit();
            return $this->getAjaxInfoResult($welcomeLesson);
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()->logError('welcome-lesson/move', $exception->getMessage(), true);
            return self::getJsonErrorResult('Server error');
        }
    }

    public function actionMove()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $welcomeLessonData = Yii::$app->request->post('welcome_lesson', []);
        if (!isset($welcomeLessonData['id'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        if (!$welcomeLesson = WelcomeLesson::findOne($welcomeLessonData['id'])) {
            return self::getJsonErrorResult('Welcome lesson is not found');
        }
        
        if (!empty($welcomeLessonData['group_proposal'])) {
            $group = Group::findOne($welcomeLessonData['group_proposal']);
        } else {
            $group = Group::findOne($welcomeLessonData['group_id']);
        }

        if (!$group || $group->active != Group::STATUS_ACTIVE) {
            return self::getJsonErrorResult('Group not found');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            GroupComponent::addPupilToGroup($welcomeLesson->user, $group, $welcomeLesson->lessonDateTime);
            MoneyComponent::setUserChargeDates($welcomeLesson->user, $group);
            $welcomeLesson->status = WelcomeLesson::STATUS_SUCCESS;
            $welcomeLesson->save();
            $transaction->commit();
            return $this->getAjaxInfoResult($welcomeLesson);
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()->logError('welcome-lesson/move', $exception->getMessage(), true);
            return self::getJsonErrorResult($exception->getMessage());
        }
    }

    /**
     * Finds the Feedback model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return WelcomeLesson the loaded model
     * @throws yii\web\NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = WelcomeLesson::findOne($id)) !== null) {
            return $model;
        } else {
            throw new yii\web\NotFoundHttpException('The requested page does not exist.');
        }
    }
}
