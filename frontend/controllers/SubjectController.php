<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Module;
use common\models\Quiz;
use common\models\Subject;
use common\models\SubjectCategory;
use common\models\Webpage;
use yii\web\NotFoundHttpException;

class SubjectController extends Controller
{
    /**
     * Displays a single Subject model.
     * @param string $id
     * @param Webpage $webpage
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id, $webpage)
    {
        $subject = $this->findModel($id);
        $quizWebpage = Webpage::findOne(['module_id' => Module::getModuleIdByControllerAndAction('quiz', 'list')]);
        return $this->render('view', [
            'subject' => $subject,
            'webpage' => $webpage,
            'h1' => $subject->name,
            'quizCount' => Quiz::find()->where(['subject_id' => $subject->id])->count(),
            'quizWebpage' => $quizWebpage,
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionList()
    {
        /** @var SubjectCategory[] $categories */
        $categories = SubjectCategory::find()->with('activeSubjects')->all();
        $jsonData = [];
        foreach ($categories as $category) {
            $subjects = [];
            foreach ($category->activeSubjects as $subject) {
                $subjects[] = ['id' => $subject->id, 'name' => $subject->name];
            }
            $jsonData[] = ['name' => $category->name, 'subjects' => $subjects];
        }
        return $this->asJson($jsonData);
    }

    /**
     * Finds the Subject model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Subject the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Subject::findOne($id)) !== null && $model->active == Subject::STATUS_ACTIVE) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
