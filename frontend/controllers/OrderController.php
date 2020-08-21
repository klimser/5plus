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
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionCreate()
    {
        if (!Yii::$app->request->isAjax) throw new BadRequestHttpException('Wrong request');

        $orderData = Yii::$app->request->post('order');
        $order = new Order(['scenario' => Order::SCENARIO_FRONTEND]);
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
                ComponentContainer::getErrorLogger()
                    ->logError('Order.create', $order->getErrorsAsString());
                $jsonData = self::getJsonErrorResult('Server error');
                $jsonData['errors'] = $order->getErrorsAsString();
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * Creates a new Order model.
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionCreateOnline()
    {
        if (!Yii::$app->request->isAjax) throw new BadRequestHttpException('Wrong request');

        $orderData = Yii::$app->request->post('order');
        $order = new Order(['scenario' => Order::SCENARIO_FRONTEND]);
        $order->load($orderData, '');
        if (Yii::$app->request->post('g-recaptcha-response')) $order->reCaptcha = Yii::$app->request->post('g-recaptcha-response');

        $order->subject = 'Онлайн-школа: ' . $order->subject;
        if ($order->save(true)) {
            $order->notifyAdmin();
            $jsonData = self::getJsonOkResult();
        } else {
            ComponentContainer::getErrorLogger()
                ->logError('Order.create', $order->getErrorsAsString());
            $jsonData = self::getJsonErrorResult('Server error');
            $jsonData['errors'] = $order->getErrorsAsString();
        }

        return $this->asJson($jsonData);
    }
}
