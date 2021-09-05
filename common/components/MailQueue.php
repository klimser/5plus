<?php

namespace common\components;

use common\models\EmailQueue;
use yii\base\BaseObject;

class MailQueue extends BaseObject
{
    /**
     * @param string $subject
     * @param string $recipient
     * @param string|null $templateHtml
     * @param string|null $templateText
     * @param array $params
     * @return bool
     */
    public function add($subject, $recipient, $templateHtml, $templateText, $params = [])
    {
        $email = new EmailQueue();
        if ($templateHtml) $email->template_html = $templateHtml;
        if ($templateText) $email->template_text = $templateText;
        $email->params = json_encode($params, JSON_UNESCAPED_UNICODE);
        $email->sender = json_encode([\Yii::$app->params['mailFrom'] => 'Robot 5plus']);
        $email->recipient = json_encode($recipient);
        $email->subject = $subject;

        return $email->save();
    }
}
