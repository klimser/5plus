<?php

namespace frontend\controllers;

use common\models\Feedback;
use yii;
use common\components\extended\Controller;
use yii\web\Response;

/**
 * FeedbackController implements the CRUD actions for Feedback model.
 */
class FeedbackController extends Controller
{
    /**
     * Creates a new Feedback model.
     * @return Response
     * @throws yii\web\BadRequestHttpException
     */
    public function actionCreate()
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Wrong request');

        $feedback = new Feedback(['scenario' => Feedback::SCENARIO_USER]);
        $feedback->setAttributes(Yii::$app->request->post('feedback'));
        if (Yii::$app->request->post('g-recaptcha-response')) $feedback->reCaptcha = Yii::$app->request->post('g-recaptcha-response');

        $feedback->status = Feedback::STATUS_NEW;
        $feedback->ip = Yii::$app->request->userIP;

        if ($feedback->save(true)) {
            $feedback->notifyAdmin();

            $jsonData = self::getJsonOkResult();
        } else {
            Yii::$app->errorLogger->logError('Feedback.create', $feedback->getErrorsAsString(), true);
            $jsonData = self::getJsonErrorResult('Server error');
            $jsonData['errors'] = $feedback->getErrorsAsString();
        }

        return $this->asJson($jsonData);
    }
}
