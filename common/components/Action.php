<?php

namespace common\components;

use common\models\Course;
use common\models\User;
use yii\base\BaseObject;

class Action extends BaseObject
{
    public const TYPE_INCOME = 1;
    public const TYPE_CHARGE = 2;
    public const TYPE_CANCEL_AUTO = 3;
    public const TYPE_RECHARGE_AUTO = 4;
    public const TYPE_CANCEL_MANUAL = 5;
    public const TYPE_CONTRACT_ADDED = 6;
    public const TYPE_CONTRACT_PAID = 7;
    public const TYPE_COURSE_ADDED      = 11;
    public const TYPE_COURSE_UPDATED       = 12;
    public const TYPE_COURSE_STUDENT_ADDED   = 13;
    public const TYPE_COURSE_STUDENT_UPDATED = 14;
    public const TYPE_EVENT_PASSED           = 15;
    public const TYPE_EVENT_CANCELLED = 16;
    public const TYPE_EVENT_STATUS_REVERTED = 17;
    public const TYPE_WELCOME_LESSON_STATUS_CHANGED = 18;

    public const TYPE_LABELS = [
        self::TYPE_INCOME => 'Внесение админом',
        self::TYPE_CHARGE => 'Списание',
        self::TYPE_CANCEL_AUTO => 'Авто отмена', 
        self::TYPE_RECHARGE_AUTO => 'Авто перерасчёт',
        self::TYPE_CANCEL_MANUAL => 'Ручная отмена',
        self::TYPE_CONTRACT_ADDED => 'Создан договор',
        self::TYPE_CONTRACT_PAID => 'Договор оплачен',
        self::TYPE_COURSE_ADDED => 'Группа добавлена',
        self::TYPE_COURSE_UPDATED => 'Данные группы изменены',
        self::TYPE_COURSE_STUDENT_ADDED => 'Студент добавлен в группу',
        self::TYPE_COURSE_STUDENT_UPDATED => 'Изменены данные студента в группе',
        self::TYPE_EVENT_PASSED => 'Занятие отмечено проведенным',
        self::TYPE_EVENT_CANCELLED => 'Занятие отмечено отмененным',
        self::TYPE_EVENT_STATUS_REVERTED => 'Статус занятия сброшен',
        self::TYPE_WELCOME_LESSON_STATUS_CHANGED => 'Статус пробного занятия изменён',
    ];

    public function log(int $type, ?User $user = null, ?int $amount = null, ?Course $group = null, ?string $comment = null): bool
    {
        $action = new \backend\models\Action();
        $action->admin_id = \Yii::$app->user->id;
        if ($user) $action->user_id = $user->id;
        $action->type = $type;
        $action->amount = $amount;
        if ($group) $action->course_id = $group->id;
        $action->comment = $comment;

        return $action->save();
    }
}
