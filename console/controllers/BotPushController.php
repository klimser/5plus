<?php

namespace console\controllers;

use common\components\ComponentContainer;
use common\components\telegram\Request;
use common\models\BotPush;
use common\models\Notify;
use yii;
use yii\console\Controller;

/**
 * BotPushController is used to send data to telegram bot users.
 */
class BotPushController extends Controller
{
    const TIME_LIMIT = 55;

    /**
     * Search for a not sent messages and sends it.
     * @return int
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws yii\db\Exception
     */
    public function actionSend()
    {
        $currentTime = intval(date('H'));
        if (!array_key_exists('telegramPublic', \Yii::$app->components)
            || $currentTime >= 20 || $currentTime < 9) {
            echo $currentTime;
            return yii\console\ExitCode::OK;
        }

        \Yii::$app->db->open();
        ComponentContainer::getTelegramPublic()->telegram;

        $condition = ['status' => BotPush::STATUS_NEW];
        $startTime = microtime(true);
        while (true) {
            if(microtime(true) - $startTime > self::TIME_LIMIT) break;
            
            $botPush = BotPush::findOne($condition);
            if (!$botPush) {
                sleep(1);
                continue;
            }

            $botPush->status = Notify::STATUS_SENDING;
            $botPush->save();
            
            if (empty($botPush->messageArray)) {
                $botPush->dataArray = array_merge($botPush->dataArray, ['error_message' => 'Empty message!']);
                $botPush->status = BotPush::STATUS_ERROR;
                $botPush->save();
            }

            $response = Request::sendMessage(array_merge(['chat_id' => $botPush->chat_id], $botPush->messageArray));
            
            if ($response->isOk()) {
                $botPush->status = BotPush::STATUS_SENT;
            } else {
                $botPush->status = BotPush::STATUS_ERROR;
                $botPush->dataArray = array_merge(
                    $botPush->dataArray,
                    [
                        'error_code' => $response->getErrorCode(),
                        'error_message' => $response->getDescription(),
                        'result' => $response->getResult(),
                    ]
                    );
            }
            $botPush->save();
        }
        return yii\console\ExitCode::OK;
    }
}
