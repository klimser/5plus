<?php

namespace backend\controllers;

use common\models\QuizResult;
use common\models\QuizResultSearch;
use yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * OrderController implements the CRUD actions for QuizResult model.
 */
class QuizResultController extends AdminController
{
    protected $accessRule = 'manageQuiz';

    /**
     * Lists all QuizResult models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new QuizResultSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Views an existing QuizResult model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $quizResult = $this->findModel($id);

        return $this->render('view', [
            'quizResult' => $quizResult,
        ]);
    }

    /**
     * Deletes an existing QuizResult model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the QuizResult model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return QuizResult the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = QuizResult::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
