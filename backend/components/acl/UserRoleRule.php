<?php
namespace backend\components\acl;

use common\models\User;
use yii;
use yii\rbac\Rule;

/**
 * Checks if user role matches
 */
class UserRoleRule extends Rule
{
    public $name = 'userRole';

    public function execute($user, $item, $params)
    {
        if (!Yii::$app->user->isGuest) {
            $role = Yii::$app->user->identity->role;

            if ($item->name === 'root') {
                return $role == User::ROLE_ROOT;
            } elseif ($item->name === 'teacher') {
                return $role == User::ROLE_TEACHER;
            } elseif ($item->name === 'manager') {
                return $role == User::ROLE_MANAGER || $role == User::ROLE_ROOT;
            } elseif ($item->name === 'parents') {
                return $role == User::ROLE_PARENTS || $role == User::ROLE_COMPANY || $role == User::ROLE_MANAGER || $role == User::ROLE_ROOT;
            } elseif ($item->name === 'pupil') {
                return $role == User::ROLE_PUPIL || $role == User::ROLE_PARENTS || $role == User::ROLE_COMPANY || $role == User::ROLE_MANAGER || $role == User::ROLE_ROOT;
            }
        }
        return false;
    }
}
