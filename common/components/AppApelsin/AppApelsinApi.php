<?php

namespace common\components\AppApelsin;

use yii\base\BaseObject;

/**
 * Class AppApelsinApi
 * @package common\components
 * @property string $login
 * @property string $password
 */
class AppApelsinApi extends BaseObject
{
    protected string $login;
    protected string $password;

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
