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
        $telegram = ComponentContainer::getTelegramPublic();
        try {
            while (true) {
                $telegram->telegram->handleGetUpdates(null, 300);
                $lastResponse = $telegram->telegram->getLastCommandResponse();
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
