<?php

namespace console\controllers;

use common\components\paygram\PaygramApiException;
use common\components\PaymentComponent;
use common\components\telegram\Request;
use common\models\Notify;
use yii;
use yii\console\Controller;

/**
 * NotifierController is used to send notifications to users.
 */
class NotifierController extends Controller
{
    /**
     * Search for a not sent e-mail and sends it.
     * @return int
     */
    public function actionSend()
    {
        $condition = ['state' => Notify::STATUS_NEW];
        
        $tryTelegram = false;
        if (array_key_exists('telegramPublic', \Yii::$app->components)) {
            \Yii::$app->telegramPublic->telegram;
            $tryTelegram = true;
        }
        
        while (true) {
            $toSend = Notify::findOne($condition);
            if (!$toSend) break;

            $toSend->status = Notify::STATUS_SENDING;
            $toSend->save();

            $sendSms = true;
            if ($tryTelegram && $toSend->user->tg_chat_id) {
                $message = null;
                switch ($toSend->template_id) {
                    case 1:
                        $message = "У вас задолженность {$toSend->params['debt']}."
                            . ' [Оплатить онлайн](' . PaymentComponent::getPaymentLink($toSend->user_id, $toSend->params['group_id'])->url . ')';
                        break;
                    case 2:
                        $message = "У вас осталось {$toSend->params['paid_lessons']} занятий."
                            . ' [Оплатить онлайн](' . PaymentComponent::getPaymentLink($toSend->user_id, $toSend->params['group_id'])->url . ')';
                        break;
                }
                if ($message) {
                    /** @var \Longman\TelegramBot\Entities\ServerResponse $response */
                    $response = Request::sendMessage([
                            'chat_id' => $toSend->user->tg_chat_id,
                            'parse_mode' => 'Markdown',
                            'text' => $message
                        ]);
                    if ($response->isOk()) {
                        $sendSms = false;
                        $toSend->status = Notify::STATUS_SENT;
                        $toSend->save();
                    }
                }
            }

            if ($sendSms) {
                try {
                    \Yii::$app->paygramApi->sendSms($toSend->template_id, $toSend->user->phoneFull, $toSend->params);
                    $toSend->status = Notify::STATUS_SENT;
                } catch (PaygramApiException $exception) {
                    $toSend->status = Notify::STATUS_ERROR;
                }
                $toSend->save();
            }
        }
        return yii\console\ExitCode::OK;
    }
}