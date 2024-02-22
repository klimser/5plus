<?php

namespace console\controllers;

use common\components\ComponentContainer;
use Longman\TelegramBot\Request;
use common\models\BotMailing;
use common\models\User;
use yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * BotMailingController is used to send mailing to telegram bot users.
 */
class BotMailingController extends Controller
{
    const TIME_LIMIT = 55;

    /**
     * Search for a not sent telegram mailing and sends it.
     * @return int
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws yii\db\Exception
     */
    public function actionSend()
    {
        if (!array_key_exists('telegramPublic', \Yii::$app->components)) {
            return ExitCode::OK;
        }

        \Yii::$app->db->open();
        ComponentContainer::getTelegramPublic()->telegram;

        $startTime = microtime(true);
        while (microtime(true) - $startTime < self::TIME_LIMIT) {
            /** @var BotMailing $botMailing */
            $botMailing = BotMailing::find()
                ->andWhere(['finished_at' => null])
                ->andWhere(['or', ['started_at' => null], ['<', 'started_at', date('Y-m-d H:i:s')]])
                ->one();
            if (!$botMailing) {
                return ExitCode::OK;
            }

            if (($botMailing->process_result['status'] ?? 'new') === 'sending') {
                sleep(5);
                continue;
            }

            $processResult = $botMailing->process_result;
            $processResult['status'] = 'sending';
            $processResult['success'] = $processResult['success'] ?? 0;
            $processResult['error'] = $processResult['error'] ?? 0;
            $botMailing->process_result = $processResult;
            if (!$botMailing->started_at) {
                $botMailing->started_at = date('Y-m-d H:i:s');
            }
            $botMailing->save();

            $photoId = $processResult['photoId'] ?? null;
            $userResultMap = $processResult['userResult'] ?? [];
            
            /** @var User[] $users */
            $users = User::find()->andWhere(['not', ['tg_chat_id' => null]])->all();
            $users = array_filter($users, function($value) { return $value->telegramSettings['subscribe']; });

            foreach ($users as $user) {
                if (array_key_exists($user->tg_chat_id, $userResultMap)) {
                    continue;
                }

                $data = [
                    'chat_id' => $user->tg_chat_id,
                    'parse_mode' => 'HTML',
                ];
                if ($botMailing->message_image) {
                    $data['caption'] = $botMailing->message_text;
                    if (!$photoId) {
                        $data['photo'] = preg_replace('#^\/\/#', 'https://', Yii::getAlias('@uploadsUrl') . $botMailing->message_image);
                    } else {
                        $data['photo'] = $photoId;
                    }
                    $response = Request::sendPhoto($data);
                } else {
                    $data['text'] = $botMailing->message_text;
                    $response = Request::sendMessage($data);
                }

                if ($response->isOk()) {
                    $userResultMap[$user->tg_chat_id] = ['status' => 'ok'];
                    $processResult['success']++;

                    if (!$photoId && $response->getResult()->getPhoto()) {
                        $processResult['photoId'] = $photoId = $response->getResult()->getPhoto()[0]->getFileId();
                    }
                } else {
                    $userResultMap[$user->tg_chat_id] = [
                        'status' => 'error',
                        'error_code' => $response->getErrorCode(),
                        'error_message' => $response->getDescription(),
                        'result' => $response->getResult(),
                    ];
                    $processResult['error']++;
                }

                $processResult['userResult'] = $userResultMap;
                $botMailing->process_result = $processResult;
                $botMailing->save();

                if (microtime(true) - $startTime > self::TIME_LIMIT) {
                    $processResult['status'] = 'paused';
                    $botMailing->process_result = $processResult;
                    $botMailing->save();
                    break;
                }
            }
            
            if ($processResult['status'] === 'sending') {
                $processResult['status'] = 'finished';
                $botMailing->process_result = $processResult;
                $botMailing->finished_at = date('Y-m-d H:i:s');
                $botMailing->save();
            }
        }
        return ExitCode::OK;
    }
}
