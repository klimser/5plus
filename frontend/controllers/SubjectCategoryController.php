<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Module;
use common\models\Quiz;
use common\models\Subject;
use common\models\SubjectCategory;
use common\models\Webpage;
use yii\web\NotFoundHttpException;

class SubjectCategoryController extends Controller
{
    /**
     * Displays a Subjects page.
     * @param $webpage Webpage
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($webpage)
    {
        $subjectCategory = $this->findModel($webpage->record_id);

        return $this->render('view', [
            'subjectCategory' => $subjectCategory,
            'webpage' => $webpage,
            'h1' => $subjectCategory->name,
        ]);
    }

    /**
     * Finds the Subject model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return SubjectCategory the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SubjectCategory::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
