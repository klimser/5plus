<?php

namespace common\components;

use backend\models\EventMember;
use common\components\telegram\text\PublicMain;
use common\models\GroupPupil;
use common\models\Notify;
use common\models\User;
use yii\base\BaseObject;

class BotPush extends BaseObject
{
    private function add(int $chatId, array $message): bool
    {
        $botPush = new \common\models\BotPush();
        $botPush->chat_id = $chatId;
        $botPush->messageArray = $message;
        return $botPush->save();
    }

    /**
     * @param EventMember $eventMember
     * @return bool
     */
    public function attendance(EventMember $eventMember)
    {
        $user = $eventMember->groupPupil->user;
        if (!$user->tg_chat_id || !$user->telegramSettings['subscribe']) {
            return true;
        }
        
        $message = sprintf(
            $eventMember->status === EventMember::STATUS_ATTEND ? PublicMain::ATTENDANCE_ATTEND : PublicMain::ATTENDANCE_MISS,
            $user->telegramSettings['trusted'] ? $user->name : $user->nameHidden,
            $eventMember->event->group->legal_name
        );
        
        if ($this->add($user->tg_chat_id, ['text' => $message])) {
            return true;
        }
        return false;
    }

    /**
     * @param EventMember $eventMember
     * @return bool
     */
    public function mark(EventMember $eventMember)
    {
        $user = $eventMember->groupPupil->user;
        if (!$user->tg_chat_id || !$user->telegramSettings['subscribe']) {
            return true;
        }
        
        $usersCount = User::find()->andWhere(['tg_chat_id' => $user->tg_chat_id])->count();

        $message = sprintf(
            PublicMain::ATTENDANCE_MARK,
            $usersCount > 1 ? ($user->telegramSettings['trusted'] ? $user->name : $user->nameHidden) . ': ' : '',
            $eventMember->mark,
            $eventMember->event->group->legal_name
        );

        if ($this->add($user->tg_chat_id, ['text' => $message])) {
            return true;
        }
        return false;
    }

    /**
     * @param GroupPupil $groupPupil
     * @return bool
     */
    public function lowBalance(GroupPupil $groupPupil)
    {
        $user = $groupPupil->user;
        if (!$user->tg_chat_id || !$user->telegramSettings['subscribe']) {
            return true;
        }

        $lessonDebt = GroupPupil::find()
            ->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupPupil->group_id])
            ->andWhere(['<', 'paid_lessons', 0])
            ->select('SUM(paid_lessons)')
            ->scalar();
        return ComponentContainer::getNotifyQueue()->add(
            $groupPupil->user,
            Notify::TEMPLATE_PUPIL_DEBT,
            ['debt' => abs($lessonDebt)],
            $groupPupil->group
        );
    }
}
