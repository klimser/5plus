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
            $group = Yii::$app->user->identity->role;
            switch ($group) {
                case User::ROLE_PUPIL: return $params['user'] === $user; break;
                case User::ROLE_PARENTS: {
                    if ($params['user'] === $user) return true;
                    /** @var User $pupil */
                    $pupil = User::findOne($params['user']);
                    return $pupil != null && $pupil->parent_id === $user;
                } break;
            }
        }
        return false;
    }
}