<?php

namespace common\components;

use yii;
use backend\models\User;
use yii\base\BaseObject;

class Action extends BaseObject
{
    const TYPE_INCOME = 1;
    const TYPE_CHARGE = 2;
    const TYPE_CANCEL_AUTO = 3;
    const TYPE_RECHARGE_AUTO = 4;
    const TYPE_CANCEL_MANUAL = 5;

    public static $typeLabels = [
        self::TYPE_INCOME => 'Внесение админом',
        self::TYPE_CHARGE => 'Списание',
        self::TYPE_CANCEL_AUTO => 'Авто отмена', 
        self::TYPE_RECHARGE_AUTO => 'Авто перерасчёт',
        self::TYPE_CANCEL_MANUAL => 'Ручная отмена',
    ];

    /**
     * @param User $user
     * @param int $type
     * @param int $amount
     * @param int|null $groupId
     * @param string|null $comment
     * @return bool
     */
    public function log(User $user, $type, $amount, $groupId = null, $comment = null)
    {
        $action = new \backend\models\Action();
        $action->admin_id = Yii::$app instanceof \yii\console\Application ? User::SYSTEM_USER_ID : \Yii::$app->user->id;
        $action->user_id = $user->id;
        $action->type = $type;
        $action->amount = $amount;
        if ($groupId) $action->group_id = $groupId;
        $action->comment = $comment;

        return $action->save();
    }
}