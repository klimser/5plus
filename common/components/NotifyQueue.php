<?php

namespace common\components;

use common\models\Course;
use common\models\Notify;
use common\models\User;
use yii\base\BaseObject;

class NotifyQueue extends BaseObject
{
    /**
     * @param array<string,mixed> $params
     */
    public function add(User $recipient, int $templateId, array $params, ?Course $course = null): bool
    {
        $notification = new Notify();
        $notification->user_id = $recipient->id;
        $notification->course_id = $course?->id;
        $notification->template_id = $templateId;
        $notification->parameters = $params;
        $notification->status = Notify::STATUS_NEW;

        return $notification->save();
    }
}