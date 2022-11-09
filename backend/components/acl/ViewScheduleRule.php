<?php

namespace backend\components\acl;

use common\models\User;
use yii;
use yii\rbac\Rule;

/**
 * Checks if user group matches
 */
class ViewScheduleRule extends Rule
{
    public $name = 'canViewSchedule';

    public function execute($user, $item, $params)
    {
        if (isset($params['user']) && $params['user'] && !Yii::$app->user->isGuest) {
            $role = Yii::$app->user->identity->role;
            switch ($role) {
                case User::ROLE_STUDENT:
                    return $params['user'] === $user;
                case User::ROLE_PARENTS:
                {
                    if ($params['user'] === $user) {
                        return true;
                    }
                    /** @var User $student */
                    $student = User::findOne($params['user']);

                    return $student != null && $student->parent_id === $user;
                }
            }
        }

        return false;
    }
}