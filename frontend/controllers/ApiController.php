<?php

namespace frontend\controllers;

use common\components\Telegram;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\TelegramLog;
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
        \Yii::$app->db->open();
        try {
            /** @var Telegram $telegram */
            $telegram = \Yii::$app->telegramAdminNotifier;

            if (!$telegram->checkAccess(\Yii::$app->request)) throw new HttpException(403, 'Access denied');

            $telegram->telegram->handle();
        } catch (TelegramException $e) {
            TelegramLog::error($e);
        }
    }
}