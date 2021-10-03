<?php

namespace console\controllers;

use common\components\ComponentContainer;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\TelegramLog;
use yii;
use yii\console\Controller;

class BotHandleController extends Controller
{
    public function actionRun()
    {
        Yii::$app->db->open();
        $telegram = ComponentContainer::getTelegramPublic()->telegram;
        try {
            while (true) {
                $lastResponse = $telegram->handleGetUpdates(['timeout' => 3600]);
                if (!$lastResponse->isOk()) {
                    TelegramLog::error('NOk response. ' . $lastResponse->getErrorCode() . ': ' . $lastResponse->getDescription(), ['response' => $lastResponse]);
                }
            }
        } catch (TelegramException $e) {
            TelegramLog::error($e);
        }
        return yii\console\ExitCode::OK;
    }
}
