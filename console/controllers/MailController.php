<?php

namespace console\controllers;

use common\components\ComponentContainer;
use Longman\TelegramBot\Request;
use common\models\EmailQueue;
use common\models\GiftCard;
use Longman\TelegramBot\DB;
use Yii;
use yii\console\Controller;

/**
 * MailController is used to send e-mails from queue.
 */
class MailController extends Controller
{
    /**
     * Search for a not sent e-mail and sends it.
     * @return int
     */
    public function actionSend()
    {
        $condition = ['state' => EmailQueue::STATUS_NEW];

        $tryTelegram = false;
        if (array_key_exists('telegramAdminNotifier', Yii::$app->components)) {
            Yii::$app->db->open();
            ComponentContainer::getTelegramAdminNotifier()->initBot();
            $subscribed = DB::selectChats([]);
            if (!empty($subscribed)) $tryTelegram = true;
        }

        while ($toSend = EmailQueue::findOne($condition)) {
            $toSend->state = EmailQueue::STATUS_SENDING;
            $toSend->save();

            $params = $toSend->params ? json_decode($toSend->params, true) : [];

            $sendEmail = true;
            if ($tryTelegram) {
                $message = match ($toSend->template_html) {
                    'order-html' => "На сайте посетитель {$params['userName']} оставил заявку на занятие \"{$params['subjectName']}\".\n"
                        . '[Обработать заявку](https://cabinet.5plus.uz/order/index)',
                    'review-html' => "На сайте посетитель {$params['userName']} оставил отзыв, проверьте его содержание и утвердите или отклоните его.\n"
                        . '[Обработать отзыв](https://cabinet.5plus.uz/review/index)',
                    'feedback-html' => "На сайте посетитель {$params['userName']} оставил сообщение через форму обратной связи.\n"
                        . '[Обработать сообщение](https://cabinet.5plus.uz/feedback/index)',
                    default => null,
                };
                if ($message) {
                    /** @var \Longman\TelegramBot\Entities\ServerResponse[] $results */
                    $results = Request::sendToActiveChats(
                        'sendMessage',
                        ['parse_mode' => 'Markdown', 'text' => $message],
                        [
                            'groups' => true,
                            'supergroups' => true,
                            'channels' => false,
                            'users' => true,
                        ]
                    );
                    foreach ($results as $result) {
                        if ($result->isOk()) {
                            $sendEmail = false;
                            $toSend->state = EmailQueue::STATUS_SENT;
                            $toSend->save();
                            break;
                        }
                    }
                }
            }

            if ($sendEmail) {
                $message = Yii::$app->mailer->compose(['html' => $toSend->template_html, 'text' => $toSend->template_text], $params)
                    ->setFrom(json_decode($toSend->sender, true))
                    ->setTo(json_decode($toSend->recipient, true))
                    ->setSubject($toSend->subject);
                if ($toSend->template_html === 'gift-card-html') {
                    $giftCard = GiftCard::findOne($params['id']);
                    if ($giftCard) {
                        $giftCardDoc = new \common\resources\documents\GiftCard($giftCard);
                        $message->attachContent($giftCardDoc->save(), ['fileName' => 'flyer.pdf', 'contentType' => 'application/pdf']);
                    }
                }
                $toSend->state = $message->send() ? EmailQueue::STATUS_SENT : EmailQueue::STATUS_ERROR;
                $toSend->save();
            }
        }
        return yii\console\ExitCode::OK;
    }
}
