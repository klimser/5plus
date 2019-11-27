<?php

namespace console\controllers;

use common\components\ComponentContainer;
use common\models\BotQueue;
use yii;
use yii\console\Controller;

/**
 * PyBotController is used to send commands to Python bot.
 */
class PyBotController extends Controller
{
    const TIME_LIMIT = 58;

    /**
     * Search for a not sent requests and sends it.
     * @return int
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
     */
    public function actionProcess()
    {
        $currentTime = intval(date('H'));
        if ($currentTime >= 21 || $currentTime < 8) return yii\console\ExitCode::OK;

        $startTime = microtime(true);
        $exceptIds = [];
        $pyBotComponent = ComponentContainer::getPyBot();
        while (true) {
            $currentTime = microtime(true);
            if ($currentTime - $startTime > self::TIME_LIMIT) break;
            
            /** @var BotQueue $toSend */
            $toSend = BotQueue::find()
                ->andWhere(['or', ['lock' => null], ['<', 'lock', date_create('-2 minutes')->format('Y-m-d H:i:s')]])
                ->andWhere(['not', ['in', 'id', $exceptIds]])
                ->one();
            if (!$toSend) {
                sleep(2);
                continue;
            }

            $toSend->lock = date('Y-m-d H:i:s');
            $toSend->save();

            $result = $pyBotComponent->process($toSend);
            if ($result) {
                $toSend->delete();
            } else {
                $toSend->lock = null;
                $toSend->save();
                $exceptIds[] = $toSend->id;
            }
        }
        return yii\console\ExitCode::OK;
    }
}
