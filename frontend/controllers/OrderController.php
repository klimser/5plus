<?php

namespace frontend\controllers;

use common\components\ComponentContainer;
use common\components\extended\Controller;
use common\models\Order;
use common\models\Subject;
use Yii;
use yii\web\Response;
use yii\web\BadRequestHttpException;

/**
 * OrderController implements the CRUD actions for Order model.
 */
class OrderController extends Controller
{
    /**
     * Creates a new Order model.
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionCreate()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $orderData = Yii::$app->request->post('order');
        $order = new Order(['scenario' => Order::SCENARIO_FRONTEND]);
        $order->load($orderData, '');
        if (Yii::$app->request->post('g-recaptcha-response')) $order->reCaptcha = Yii::$app->request->post('g-recaptcha-response');
        $subject = Subject::findOne($order->subject);
        if (!$subject) return self::getJsonErrorResult('Неверный запрос');
        
        $order->subject = $subject->name;
        if (!$order->save(true)) {
            ComponentContainer::getErrorLogger()
                ->logError('Order.create', $order->getErrorsAsString());
            $jsonData = self::getJsonErrorResult('Server error');
            $jsonData['errors'] = $order->getErrorsAsString();
            return $jsonData;
        }

        $order->notifyAdmin();
        return self::getJsonOkResult();
    }

    /**
     * Creates a new Order model.
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionCreateOnline()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $orderData = Yii::$app->request->post('order');
        $order = new Order(['scenario' => Order::SCENARIO_FRONTEND]);
        $order->load($orderData, '');
        if (Yii::$app->request->post('g-recaptcha-response')) $order->reCaptcha = Yii::$app->request->post('g-recaptcha-response');

        $order->subject = 'Онлайн-школа: ' . $order->subject;
        if (!$order->save(true)) {
            ComponentContainer::getErrorLogger()
                ->logError('Order.create', $order->getErrorsAsString());
            $jsonData = self::getJsonErrorResult('Server error');
            $jsonData['errors'] = $order->getErrorsAsString();
            return $jsonData;
        }

        $order->notifyAdmin();
        return self::getJsonOkResult();
    }
}
