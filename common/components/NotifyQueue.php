<?php

namespace common\components;

use common\models\Notify;
use common\models\User;
use yii\base\BaseObject;

class NotifyQueue extends BaseObject
{
    /**
     * @param User $recipient
     * @param int $templateId
     * @param array $params
     * @return bool
     */
    public function add(User $recipient, int $templateId, array $params)
    {
        $notification = new Notify();
        $notification->user_id = $recipient->id;
        $notification->template_id = $templateId;
        $notification->params = $params;
        $notification->status = Notify::STATUS_NEW;

        return $notification->save();
    }
}