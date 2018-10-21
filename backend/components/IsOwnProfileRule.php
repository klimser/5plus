<?php

namespace backend\components;

use yii;
use yii\rbac\Rule;


/**
 * Checks if user group matches
 */
class IsOwnProfileRule extends Rule
{
    public $name = 'isOwnProfile';

    public function execute($user, $item, $params)
    {
        return isset($params['user']) && $params['user'] && $params['user'] === $user;
    }
}