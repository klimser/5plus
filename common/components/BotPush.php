<?php

namespace common\components;

use backend\models\EventMember;
use common\components\telegram\text\PublicMain;
use common\models\CourseStudent;
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
     */
    public function attendance(EventMember $eventMember)
    {
        $user = $eventMember->groupPupil->user;
        if ($user->tg_chat_id && $user->telegramSettings['subscribe']) {
            $message = sprintf(
                $eventMember->status === EventMember::STATUS_ATTEND ? PublicMain::ATTENDANCE_ATTEND : PublicMain::ATTENDANCE_MISS,
                $user->telegramSettings['trusted'] ? $user->name : $user->nameHidden,
                $eventMember->event->group->legal_name
            );

            $this->add($user->tg_chat_id, ['text' => $message]);
        }

        if ($user->parent_id && $user->parent->tg_chat_id && $user->parent->telegramSettings['subscribe']) {
            $message = sprintf(
                $eventMember->status === EventMember::STATUS_ATTEND ? PublicMain::ATTENDANCE_ATTEND : PublicMain::ATTENDANCE_MISS,
                $user->parent->telegramSettings['trusted'] ? $user->name : $user->nameHidden,
                $eventMember->event->group->legal_name
            );

            $this->add($user->parent->tg_chat_id, ['text' => $message]);
        }
    }

    /**
     * @param EventMember $eventMember
     */
    public function mark(EventMember $eventMember)
    {
        $user = $eventMember->groupPupil->user;
        if ($user->tg_chat_id && $user->telegramSettings['subscribe']) {
            $usersCount = User::find()->andWhere(['tg_chat_id' => $user->tg_chat_id])->count();

            $message = sprintf(
                PublicMain::ATTENDANCE_MARK,
                $usersCount > 1 ? ($user->telegramSettings['trusted'] ? $user->name : $user->nameHidden) . ': ' : '',
                $eventMember->mark,
                $eventMember->event->group->legal_name
            );

            $this->add($user->tg_chat_id, ['text' => $message]);
        }

        if ($user->parent_id && $user->parent->tg_chat_id && $user->parent->telegramSettings['subscribe']) {
            $message = sprintf(
                PublicMain::ATTENDANCE_MARK,
                $user->parent->telegramSettings['trusted'] ? $user->name : $user->nameHidden,
                $eventMember->mark,
                $eventMember->event->group->legal_name
            );

            $this->add($user->parent->tg_chat_id, ['text' => $message]);
        }
    }

    /**
     * @param CourseStudent $groupPupil
     */
    public function lowBalance(CourseStudent $groupPupil)
    {
        $user = $groupPupil->user;
        
        $lessonDebt = CourseStudent::find()
            ->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupPupil->group_id])
            ->andWhere(['<', 'paid_lessons', 0])
            ->select('SUM(paid_lessons)')
            ->scalar();
        
        if ($user->tg_chat_id && $user->telegramSettings['subscribe']) {
            ComponentContainer::getNotifyQueue()->add(
                $user,
                Notify::TEMPLATE_PUPIL_DEBT,
                ['debt' => abs($lessonDebt)],
                $groupPupil->group
            );
        }

        if ($user->parent_id && $user->parent->tg_chat_id && $user->parent->telegramSettings['subscribe']) {
            ComponentContainer::getNotifyQueue()->add(
                $user->parent,
                Notify::TEMPLATE_PARENT_DEBT,
                ['debt' => abs($lessonDebt), 'child_id' => $groupPupil->user_id],
                $groupPupil->group
            );
        }
    }
}
