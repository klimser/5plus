<?php

namespace frontend\controllers;

use common\components\click\ClickServer;
use common\components\ComponentContainer;
use common\components\payme\PaymeServer;
use common\components\paymo\PaymoServer;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\TelegramLog;
use Yii;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\Response;

/**
 * ApiController is used to provide API-messaging
 */
class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionTgAdminBot()
    {
        Yii::$app->db->open();
        try {
            $telegram = ComponentContainer::getTelegramAdminNotifier();

            if (!$telegram->checkAccess(Yii::$app->request)) throw new HttpException(403, 'Access denied');

            $telegram->telegram->handle();
        } catch (TelegramException $e) {
            TelegramLog::error($e);
        }
    }

    public function actionTgPublicBot()
    {
        Yii::$app->db->open();
        try {
            $telegram = ComponentContainer::getTelegramPublic();

            if (!$telegram->checkAccess(Yii::$app->request)) throw new HttpException(403, 'Access denied');

            $telegram->telegram->handle();
        } catch (TelegramException $e) {
            TelegramLog::error($e);
        }
    }

    public function actionPaymoComplete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $jsonData = (new PaymoServer())->processPaymoRequest();
        if (!array_key_exists('status', $jsonData) || $jsonData['status'] != 1) {
            ComponentContainer::getErrorLogger()->logError(
                'api/paymo',
                print_r(Yii::$app->request, true) . "\n" . print_r($jsonData, true),
                true
            );
        }

        return $jsonData;
    }

    public function actionClickPrepare()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return (new ClickServer())->processPrepare();
    }

    public function actionClickComplete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return (new ClickServer())->processComplete();
    }

    public function actionPayme()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return (new PaymeServer())->handle();
    }
}
