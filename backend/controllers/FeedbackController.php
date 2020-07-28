<?php

namespace backend\controllers;

use common\models\Feedback;
use common\models\FeedbackSearch;
use yii;
use yii\web\NotFoundHttpException;

/**
 * FeedbackController implements the CRUD actions for Feedback model.
 */
class FeedbackController extends AdminController
{
    protected $accessRule = 'manageFeedbacks';

    /**
     * Lists all Feedback models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new FeedbackSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     */
    public function actionChangeStatus($id)
    {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $feedback = $this->findModel($id);

            $newStatus = Yii::$app->request->post('status');
            if ($feedback->status != Feedback::STATUS_NEW && $newStatus == Feedback::STATUS_NEW) {
                $jsonData = self::getJsonErrorResult('Статус "Новый" не может быть установлен сообщению со статусом "' . Feedback::STATUS_LABELS[$feedback->status] . '"');
            } else {
                $feedback->status = $newStatus;
                $isError = false;
                if (!$isError) {
                    if ($feedback->save()) {
                        $jsonData = self::getJsonOkResult([
                            'id' => $feedback->id,
                            'state' => $newStatus,
                        ]);
                    } else $jsonData = self::getJsonErrorResult($feedback->getErrorsAsString('status'));
                }
            }
        }
        return $this->asJson($jsonData);
    }

    /**
     * Deletes an existing Feedback model.
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
     * Finds the Feedback model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Feedback the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Feedback::findOne($id)) !== null) {
            $model->scenario = Feedback::SCENARIO_ADMIN;
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
