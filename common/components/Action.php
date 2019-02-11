<?php

namespace common\components;

use common\models\Group;
use common\models\User;
use yii\base\BaseObject;

class Action extends BaseObject
{
    const TYPE_INCOME = 1;
    const TYPE_CHARGE = 2;
    const TYPE_CANCEL_AUTO = 3;
    const TYPE_RECHARGE_AUTO = 4;
    const TYPE_CANCEL_MANUAL = 5;
    const TYPE_CONTRACT_ADDED = 6;
    const TYPE_CONTRACT_PAID = 7;
    const TYPE_GROUP_ADDED = 11;
    const TYPE_GROUP_UPDATED = 12;
    const TYPE_GROUP_PUPIL_ADDED = 13;
    const TYPE_GROUP_PUPIL_UPDATED = 14;

    public static $typeLabels = [
        self::TYPE_INCOME => 'Внесение админом',
        self::TYPE_CHARGE => 'Списание',
        self::TYPE_CANCEL_AUTO => 'Авто отмена', 
        self::TYPE_RECHARGE_AUTO => 'Авто перерасчёт',
        self::TYPE_CANCEL_MANUAL => 'Ручная отмена',
        self::TYPE_CONTRACT_ADDED => 'Создан договор',
        self::TYPE_CONTRACT_PAID => 'Договор оплачен',
        self::TYPE_GROUP_ADDED => 'Группа добавлена',
        self::TYPE_GROUP_UPDATED => 'Данные группы изменены',
        self::TYPE_GROUP_PUPIL_ADDED => 'Студент добавлен в группу',
        self::TYPE_GROUP_PUPIL_UPDATED => 'Изменены данные студента в группе',
    ];

    /**
     * @param int $type
     * @param User $user
     * @param int $amount
     * @param Group|null $group
     * @param string|null $comment
     * @return bool
     */
    public function log(int $type, ?User $user = null, ?int $amount = null, ?Group $group = null, $comment = null)
    {
        $action = new \backend\models\Action();
        $action->admin_id = \Yii::$app->user->id;
        if ($user) $action->user_id = $user->id;
        $action->type = $type;
        $action->amount = $amount;
        if ($group) $action->group_id = $group->id;
        $action->comment = $comment;

        return $action->save();
    }
}