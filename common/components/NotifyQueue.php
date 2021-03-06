<?php

namespace common\components;

use common\models\Group;
use common\models\Notify;
use common\models\User;
use yii\base\BaseObject;

class NotifyQueue extends BaseObject
{
    /**
     * @param User $recipient
     * @param int $templateId
     * @param array $params
     * @param Group|null $group
     * @return bool
     */
    public function add(User $recipient, int $templateId, array $params, ?Group $group = null)
    {
        $notification = new Notify();
        $notification->user_id = $recipient->id;
        $notification->group_id = $group ? $group->id : null;
        $notification->template_id = $templateId;
        $notification->parameters = $params;
        $notification->status = Notify::STATUS_NEW;

        return $notification->save();
    }
}