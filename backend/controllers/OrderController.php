<?php

namespace backend\controllers;

use common\models\OrderSearch;
use yii;
use common\models\Order;
use yii\web\NotFoundHttpException;

/**
 * OrderController implements the CRUD actions for Order model.
 */
class OrderController extends AdminController
{
    protected $accessRule = 'manageOrders';

    /**
     * Lists all Order models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionChangeStatus($id)
    {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $order = $this->findModel($id);

            $newStatus = Yii::$app->request->post('status');
            if ($order->status != Order::STATUS_UNREAD && $newStatus == Order::STATUS_UNREAD) {
                $jsonData = self::getJsonErrorResult('Статус "Новый" не может быть установлен заявке со статусом "' . Order::$statusLabels[$order->status] . '"');
            } else {
                $order->status = $newStatus;
                $isError = false;
                if ($newStatus == Order::STATUS_PROBLEM) {
                    $order->admin_comment = Yii::$app->request->post('comment');
                    if (!$order->admin_comment) {
                        $jsonData = self::getJsonErrorResult('Напишите комментарий к проблеме, иначе потом и сами забудете, и другие не поймут');
                        $isError = true;
                    }
                } else $order->admin_comment = null;
                if (!$isError) {
                    if ($order->save()) {
                        $jsonData = self::getJsonOkResult([
                            'id' => $order->id,
                            'state' => $newStatus,
                        ]);
                    } else $jsonData = self::getJsonErrorResult($order->getErrorsAsString('status'));
                }
            }
        }
        return $this->asJson($jsonData);
    }

    /**
     * Updates an existing Order model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $order = $this->findModel($id);
        if ($order->status == Order::STATUS_UNREAD) {
            $order->status = Order::STATUS_READ;
            $order->save();
        }

        if (Yii::$app->request->isPost) {
            if (!$order->load(Yii::$app->request->post())) \Yii::$app->session->addFlash('error', 'Form data not found');
            elseif(!$order->save()) $order->moveErrorsToFlash();
            else {
                Yii::$app->session->setFlash('success', 'Успешно обновлено');
                return $this->redirect(['update', 'id' => $order->id]);
            }
        }

        return $this->render('update', [
            'order' => $order,
        ]);
    }

    /**
     * Deletes an existing Order model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Order model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Order the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Order::findOne($id)) !== null) {
            $model->scenario = Order::SCENARIO_ADMIN;
            return $model;
        } else {
            throw new NotFoundHttpException('The requested order does not exist.');
        }
    }
}
