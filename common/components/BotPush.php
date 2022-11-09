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
        $user = $eventMember->courseStudent->user;
        if ($user->tg_chat_id && $user->telegramSettings['subscribe']) {
            $message = sprintf(
                $eventMember->status === EventMember::STATUS_ATTEND ? PublicMain::ATTENDANCE_ATTEND : PublicMain::ATTENDANCE_MISS,
                $user->telegramSettings['trusted'] ? $user->name : $user->nameHidden,
                $eventMember->event->courseConfig->legal_name
            );

            $this->add($user->tg_chat_id, ['text' => $message]);
        }

        if ($user->parent_id && $user->parent->tg_chat_id && $user->parent->telegramSettings['subscribe']) {
            $message = sprintf(
                $eventMember->status === EventMember::STATUS_ATTEND ? PublicMain::ATTENDANCE_ATTEND : PublicMain::ATTENDANCE_MISS,
                $user->parent->telegramSettings['trusted'] ? $user->name : $user->nameHidden,
                $eventMember->event->courseConfig->legal_name
            );

            $this->add($user->parent->tg_chat_id, ['text' => $message]);
        }
    }

    /**
     * @param EventMember $eventMember
     */
    public function mark(EventMember $eventMember)
    {
        $user = $eventMember->courseStudent->user;
        if ($user->tg_chat_id && $user->telegramSettings['subscribe']) {
            $usersCount = User::find()->andWhere(['tg_chat_id' => $user->tg_chat_id])->count();

            $message = sprintf(
                PublicMain::ATTENDANCE_MARK,
                $usersCount > 1 ? ($user->telegramSettings['trusted'] ? $user->name : $user->nameHidden) . ': ' : '',
                $eventMember->mark[EventMember::MARK_LESSON],
                $eventMember->event->courseConfig->legal_name
            );

            $this->add($user->tg_chat_id, ['text' => $message]);
        }

        if ($user->parent_id && $user->parent->tg_chat_id && $user->parent->telegramSettings['subscribe']) {
            $message = sprintf(
                PublicMain::ATTENDANCE_MARK,
                $user->parent->telegramSettings['trusted'] ? $user->name : $user->nameHidden,
                $eventMember->mark[EventMember::MARK_LESSON],
                $eventMember->event->courseConfig->legal_name
            );

            $this->add($user->parent->tg_chat_id, ['text' => $message]);
        }
    }

    public function lowBalance(CourseStudent $courseStudent)
    {
        $user = $courseStudent->user;
        
        $lessonDebt = CourseStudent::find()
            ->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id])
            ->andWhere(['<', 'paid_lessons', 0])
            ->select('SUM(paid_lessons)')
            ->scalar();
        
        if ($user->tg_chat_id && $user->telegramSettings['subscribe']) {
            ComponentContainer::getNotifyQueue()->add(
                $user,
                Notify::TEMPLATE_STUDENT_DEBT,
                ['debt' => abs($lessonDebt)],
                $courseStudent->course
            );
        }

        if ($user->parent_id && $user->parent->tg_chat_id && $user->parent->telegramSettings['subscribe']) {
            ComponentContainer::getNotifyQueue()->add(
                $user->parent,
                Notify::TEMPLATE_PARENT_DEBT,
                ['debt' => abs($lessonDebt), 'child_id' => $courseStudent->user_id],
                $courseStudent->course
            );
        }
    }
}
