<?php

namespace bot\controllers;

use common\components\ComponentContainer;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\TelegramLog;
use Yii;
use yii\web\Controller;
use yii\web\HttpException;

class BotController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionAdmin()
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

    public function actionPublic()
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
}
