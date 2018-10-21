<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Order;
use common\models\Subject;
use yii;
use yii\web\Response;

/**
 * OrderController implements the CRUD actions for Order model.
 */
class OrderController extends Controller
{
    /**
     * Creates a new Order model.
     * @return Response
     * @throws yii\web\BadRequestHttpException
     */
    public function actionCreate()
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Wrong request');

        $orderData = Yii::$app->request->post('order');
        $order = new Order(['scenario' => Order::SCENARIO_USER]);
        $order->load($orderData, '');
        if (Yii::$app->request->post('g-recaptcha-response')) $order->reCaptcha = Yii::$app->request->post('g-recaptcha-response');
        $subject = Subject::findOne($order->subject);
        if (!$subject) $jsonData = self::getJsonErrorResult('Неверный запрос');
        else {
            $order->subject = $subject->name;
            if ($order->save(true)) {
                $order->notifyAdmin();
                $jsonData = self::getJsonOkResult();
            } else {
                Yii::$app->errorLogger->logError('Order.create', $order->getErrorsAsString());
                $jsonData = self::getJsonErrorResult('Server error');
                $jsonData['errors'] = $order->getErrorsAsString();
            }
        }

        return $this->asJson($jsonData);
    }
}