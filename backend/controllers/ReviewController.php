<?php

namespace backend\controllers;

use common\models\Module;
use common\models\Review;
use common\models\ReviewSearch;
use common\models\Webpage;
use yii;
use yii\web\NotFoundHttpException;

/**
 * OrderController implements the CRUD actions for Review model.
 */
class ReviewController extends AdminController
{
    protected $accessRule = 'manageReviews';

    /**
     * Lists all Review models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ReviewSearch();
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
            $review = $this->findModel($id);

            $newStatus = Yii::$app->request->post('status');
            if ($newStatus != Review::STATUS_APPROVED) $jsonData = self::getJsonErrorResult('Неправильный статус');
            else {
                $review->status = $newStatus;
                if ($review->save()) {
                    $jsonData = self::getJsonOkResult([
                        'id' => $review->id,
                        'state' => $newStatus,
                    ]);
                } else $jsonData = self::getJsonErrorResult($review->getErrorsAsString('status'));
            }
        }
        return $this->asJson($jsonData);
    }

    /**
     * Updates an existing Review model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $review = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            if (!$review->load(Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
            elseif(!$review->save()) $review->moveErrorsToFlash();
            else {
                Yii::$app->session->setFlash('success', 'Успешно обновлено');
                return $this->redirect(['update', 'id' => $review->id]);
            }
        }

        return $this->render('update', [
            'review' => $review,
        ]);
    }

    /**
     * Deletes an existing Review model.
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
     * @return string
     */
    public function actionPage()
    {
        $webpage = null;
        $moduleId = Module::getModuleIdByControllerAndAction('review', 'index');
        $webpage = Webpage::find()->where(['module_id' => $moduleId])->one();
        if (!$webpage) {
            $webpage = new Webpage();
            $webpage->module_id = $moduleId;
        }

        if (Yii::$app->request->isPost) {
            if (!$webpage->load(Yii::$app->request->post())) {
                \Yii::$app->session->addFlash('error', 'Form data not found');
            } elseif (!$webpage->save()) {
                $webpage->moveErrorsToFlash();
            } else {
                Yii::$app->session->addFlash('success', 'Изменения сохранены');
                return $this->redirect(['page']);
            }
        }

        return $this->render('page', ['webpage' => $webpage]);
    }

    /**
     * Finds the Review model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Review the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Review::findOne($id)) !== null) {
            $model->scenario = Review::SCENARIO_ADMIN;
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
