<?php

namespace common\components\AppApelsin;

use yii\base\BaseObject;

/**
 * Class AppApelsinApi
 * @package common\components
 * @property string $login
 * @property string $password
 * @property array $subjectMap
 */
class AppApelsinApi extends BaseObject
{
    protected string $login;
    protected string $password;
    protected array $subjectMap;

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

    /**
     * @return array<string,int[]>
     */
    public function getSubjectMap(): array
    {
        return $this->subjectMap;
    }

    /**
     * @param array<string,int[]> $subjectMap
     */
    public function setSubjectMap(array $subjectMap): void
    {
        $this->subjectMap = $subjectMap;
    }
}
