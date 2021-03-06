<?php

namespace frontend\controllers;

use common\models\EmailQueue;
use yii;
use yii\web\Controller;

/**
 * MailController is used to send e-mails from queue.
 */
class MailController extends Controller
{
    /**
     * Creates a new Order model.
     * @return mixed
     */
    public function actionSend()
    {
        $condition = ['state' => EmailQueue::STATUS_NEW];
        
        $toSend = EmailQueue::findOne($condition);
        
        while ($toSend) {
            $toSend->state = EmailQueue::STATUS_SENDING;
            $toSend->save();

            $params = $toSend->params ? json_decode($toSend->params, true) : [];
            if (Yii::$app->mailer->compose(['html' => $toSend->template_html, 'text' => $toSend->template_text], $params)
                ->setFrom(json_decode($toSend->sender, true))
                ->setTo(json_decode($toSend->recipient, true))
                ->setSubject($toSend->subject)
                ->send()
            ) $toSend->state = EmailQueue::STATUS_SENT;
            else $toSend->state = EmailQueue::STATUS_ERROR;
            $toSend->save();

            $toSend = EmailQueue::findOne($condition);
        }
    }
}