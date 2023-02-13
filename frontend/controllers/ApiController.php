<?php

namespace frontend\controllers;

use common\components\apelsin\ApelsinServer;
use common\components\AppApelsin\AppApelsinServer;
use common\components\AppPayme\AppPaymeServer;
use common\components\paybox\PayboxServer;
use common\components\click\ClickServer;
use common\components\ComponentContainer;
use common\components\payme\PaymeServer;
use common\components\paymo\PaymoServer;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\TelegramLog;
use Yii;
use yii\web\Controller;
use yii\web\HttpException;

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
        return (new PaymoServer())->handle(Yii::$app->request);
    }

    public function actionClickPrepare()
    {
        return (new ClickServer())->processPrepare(Yii::$app->request);
    }

    public function actionClickComplete()
    {
        return (new ClickServer())->handle(Yii::$app->request);
    }

    public function actionPayme()
    {
        return (new PaymeServer())->handle(Yii::$app->request);
    }

    public function actionAppPayme()
    {
        return (new AppPaymeServer())->handle(Yii::$app->request);
    }

    public function actionApelsin()
    {
        return (new ApelsinServer())->handle(Yii::$app->request);
    }

    public function actionAppApelsinCheck()
    {
        return (new AppApelsinServer())->handleCheck(Yii::$app->request);
    }

    public function actionAppApelsinPay()
    {
        return (new AppApelsinServer())->handlePay(Yii::$app->request);
    }

    public function actionPayboxComplete()
    {
        return (new PayboxServer())->handle(Yii::$app->request);
    }
}
